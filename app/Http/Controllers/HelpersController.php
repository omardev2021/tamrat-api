<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Letter;
use App\Models\Contact;

class HelpersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function letter(Request $request)
    {
        $request->validate([
            'email' => 'required|unique:letters',
        ]);

        $letter = new Letter();
        $letter->email = $request->email;
        $letter->save();
        return response()->json('You have successfully subscribed to our newsletter',200);


    }

    /**
     * Show the form for creating a new resource.
     */
    public function contact(Request $request)
    {
        $letter = new Contact();
        $letter->email = $request->email;
        $letter->phone = $request->phone;
        $letter->name = $request->name;
        $letter->content = $request->content;
        $letter->save();
        return response()->json('Your contact request has been sent successfully',200);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
