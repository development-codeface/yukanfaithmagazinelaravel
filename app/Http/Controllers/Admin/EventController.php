<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Http\Requests\EventRequest;

class EventController extends Controller
{

public function index()
{
    $events = Event::latest()->paginate(10);
    return view('admin.events.index',compact('events'));
}

public function create()
{
    return view('admin.events.create');
}

public function store(EventRequest $request)
{
    $data = $request->validated();

    if ($request->hasFile('image')) {

        $image = $request->file('image');

        $imageName = time().'.'.$image->getClientOriginalExtension();

        $destinationPath = public_path('uploads/events');

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $image->move($destinationPath, $imageName);

        $data['image'] = 'uploads/events/'.$imageName;
    }

    Event::create($data);

    return redirect()->route('admin.events.index')
        ->with('success','Event created');
}


public function edit(Event $event)
{
    return view('admin.events.edit',compact('event'));
}

public function update(EventRequest $request, Event $event)
{
    $data = $request->validated();

    if ($request->hasFile('image')) {

        // DELETE OLD IMAGE (important)
        if ($event->image && file_exists(public_path($event->image))) {
            unlink(public_path($event->image));
        }

        // UPLOAD NEW IMAGE
        $image = $request->file('image');

        $imageName = time().'.'.$image->getClientOriginalExtension();

        $destinationPath = public_path('uploads/events');

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $image->move($destinationPath, $imageName);

        $data['image'] = 'uploads/events/'.$imageName;
    }

    $event->update($data);

    return redirect()->route('admin.events.index')
        ->with('success','Event updated successfully');
}


public function destroy(Event $event)
{
    $event->delete();
    return back();
}

}
