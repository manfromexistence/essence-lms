@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto">
    <x-ui.card>
        <x-ui.card-header>
            <x-ui.card-title>Add Schedule Entry</x-ui.card-title>
        </x-ui.card-header>
        <x-ui.card-content>
            <form action="{{ route('dashboard.schedules.store') }}" method="POST" class="space-y-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <x-ui.select name="day_of_week" label="Day of Week" required>
                            <option value="sunday">Sunday</option>
                            <option value="monday">Monday</option>
                            <option value="tuesday">Tuesday</option>
                            <option value="wednesday">Wednesday</option>
                            <option value="thursday">Thursday</option>
                            <option value="friday">Friday</option>
                            <option value="saturday">Saturday</option>
                        </x-ui.select>
                    </div>
                    <div class="space-y-2">
                        <x-ui.select name="batch_id" label="Batch" required>
                            <option value="">Select Batch</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}">{{ $batch->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <x-ui.label for="start_time">Start Time</x-ui.label>
                        <x-ui.input type="time" name="start_time" id="start_time" required />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label for="end_time">End Time</x-ui.label>
                        <x-ui.input type="time" name="end_time" id="end_time" required />
                    </div>
                </div>

                <div class="space-y-2">
                    <x-ui.label for="subject">Subject</x-ui.label>
                    <x-ui.input type="text" name="subject" id="subject" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <x-ui.select name="teacher_id" label="Teacher (Optional)">
                            <option value="">No Teacher Assigned</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->user->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    <div class="space-y-2">
                        <x-ui.label for="room">Room Number</x-ui.label>
                        <x-ui.input type="text" name="room" id="room" />
                    </div>
                </div>

                @if($errors->any())
                    <x-ui.alert variant="destructive">
                        <x-ui.alert-title>Error</x-ui.alert-title>
                        <x-ui.alert-description>
                            <ul class="list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-ui.alert-description>
                    </x-ui.alert>
                @endif

                <div class="flex justify-end gap-4">
                    <x-ui.button type="button" variant="outline" as="a" href="{{ route('dashboard.schedules.index') }}">Cancel</x-ui.button>
                    <x-ui.button type="submit">Save Schedule</x-ui.button>
                </div>
            </form>
        </x-ui.card-content>
    </x-ui.card>
</div>
@endsection
