<style>
    .container {
        display: flex; /* or inline-flex */
        flex-wrap: wrap;
        gap:25px;
        margin-top:30px;

    }
</style>

    <x-app-layout>
        <div class="container">
            @foreach($days as $day)
                <div class="card" style="width: 25rem;">
                    <div class="card-header text-white bg-dark mb-3" style="font-size:28px;">
                        {{$day->dayName}}
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">An item</li>
                        <li class="list-group-item">A second item</li>
                        <li class="list-group-item">A third item</li>
                    </ul>
                </div>
                    @endforeach
                </div>
    </x-app-layout>
