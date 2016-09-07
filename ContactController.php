<?php

namespace App\Http\Controllers\User;

use App\Events\NewContactWasRegistered;
use App\Http\Controllers\Controller;
use App\Http\Requests\AlterContactRequest;
use Notifynder;
use Request;
use reserveio\Concierge\Models\Business;
use reserveio\Concierge\Models\Contact;

class ContactController extends Controller
{
    /*create Contact.*/
    public function create(Business $business)
    {
        $user = auth()->user();

        // Find existing registered & email
        $existingContact = $business->addressbook()->getRegisteredUserId($user->id);

        // Find existing subscribed email
        if (!$existingContact) {
            $existingContact = $business->addressbook()->getSubscribed($user->email);
        }

        // Find existing any profile for user
        if (!$existingContact) {
            $existingContact = $user->contacts()->first();
        }

        if (!$existingContact) {
            $contact = new Contact();
            return view('user.contacts.create', compact('business', 'contact'));
        }

        logger()->info("[ADVICE] Found existing contact contactId:{$existingContact->id}");

        $contact = $business->addressbook()->copyFrom($existingContact, $user->id);

        flash()->success(trans('user.contacts.msg.store.associated_existing_contact'));

        return redirect()->route('user.business.contact.show', [$business, $contact]);
    }

    /*store Contact.*/
    public function store(Business $business, AlterContactRequest $request)
    {

        $businessName = $business->name;
        Notifynder::category('user.subscribedBusiness')
                   ->from('App\Models\User', auth()->id())
                   ->to('Timegridio\Concierge\Models\Business', $business->id)
                   ->url('http://localhost')
                   ->extra(compact('businessName'))
                   ->send();

        $contact = $business->addressbook()->register(Request::all());

        $business->addressbook()->linkToUserId($contact, auth()->id());

        event(new NewContactWasRegistered($contact));

        flash()->success(trans('user.contacts.msg.store.success'));

        return redirect()->route('user.business.contact.show', [$business, $contact]);
    }

    /*show Contact.*/
    public function show(Business $business, Contact $contact)
    {
        logger()->info(__METHOD__);
        logger()->info(sprintf('businessId:%s contactId:%s', $business->id, $contact->id));

        $this->authorize('manage', $contact);

        $memberSince = $business->contacts()->find($contact->id)->pivot->created_at;

        $appointments = $contact->appointments()->orderBy('start_at')->ofBusiness($business->id)->active()->get();

        return view('user.contacts.show', compact('business', 'contact', 'appointments', 'memberSince'));
    }

    /* edit Contact.*/
    public function edit(Business $business, Contact $contact)
    {

        logger()->info(sprintf('businessId:%s contactId:%s', $business->id, $contact->id));

        $this->authorize('manage', $contact);



        return view('user.contacts.edit', compact('business', 'contact'));
    }

    /* update Contact.*/
    public function update(Business $business, Contact $contact, AlterContactRequest $request)
    {

        logger()->info(sprintf('businessId:%s contactId:%s', $business->id, $contact->id));

        $this->authorize('manage', $contact);

        $data = $request->only([
            'firstname',
            'lastname',
            'email',
            'nin',
            'gender',
            'birthdate',
            'mobile',
            'mobile_country',
        ]);

        $contact = $business->addressbook()->update($contact, $data, $request->get('notes'));

        flash()->success(trans('user.contacts.msg.update.success'));

        return redirect()->route('user.business.contact.show', [$business, $contact]);
    }

    /* delete Contact.*/
    public function destroy(Business $business, Contact $contact)
    {

        logger()->info(sprintf('businessId:%s contactId:%s', $business->id, $contact->id));

        $this->authorize('manage', $contact);

        $business->addressbook()->remove($contact);

        flash()->success(trans('user.contacts.msg.destroy.success'));

        return redirect()->route('user.business.contact.index', $business);
    }
}
