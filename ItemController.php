<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Item;

class ItemController extends Controller
{

    /*Display Items List*/

    public function index(Request $request)
    {    //echo 'hello';
        $items = Item::orderBy('id','DESC')->paginate(10);
        return view('items.index',compact('items'))
            ->with('i', ($request->input('page', 1) - 1) * 5);
    }

    /* Show the form for creating a new record.*/

    public function create()
    {
        return view('items.create');
    }

    /* Save new record to the database.*/

    public function store(Request $request)
    {
        $this->validate($request, [
            'code' => 'required',
            'name' => 'required',
            'description' => 'required',
        ]);

        Item::create($request->all());
        return redirect()->route('items.index')
                        ->with('success','Item successfully created');
    }

    /* Display the specified record. */

    public function show($id)
    {
        $item = Item::find($id);
        return view('items.show',compact('item'));
    }

    /* Display the Edit Form.*/

    public function edit($id)
    {
        $item = Item::find($id);
        return view('items.edit',compact('item'));
    }

    /* Update specified record. */

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'code' => 'required',
            'name' => 'required',
            'description' => 'required',
        ]);

        Item::find($id)->update($request->all());
        return redirect()->route('items.index')
                        ->with('success','Update successful');
    }

    /*Delete resource from database.*/
    public function delete($id)
    {
        Item::find($id)->delete();
        return redirect()->route('items.index')
                        ->with('success','Delete successful');
    }
}
