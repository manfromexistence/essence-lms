<article data-department="{{ Str::slug($member->designation ?: ($member->department ?: ($member->category->name ?? 'Other'))) }}" data-id="{{ $member->id }}" class="group cursor-pointer overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition duration-300 hover:-translate-y-1.5 hover:shadow-2xl" onclick="openTeamModal(this)">
    @php
        $name = $member->user?->name ?? 'Team Member';
        $photo = $member->avatar_url;
        $dept = $member->designation ?: ($member->department ?: ($member->category?->name ?? 'Other'));
        $bio = $member->bio ?: '';
        $links = $member->social_links ?? [];
    @endphp
    <div class="relative h-60 overflow-hidden bg-gray-100">
        <img src="{{ $photo }}" alt="{{ $name }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-105" loading="lazy" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($name) }}&background=168536&color=fff&size=512&bold=true'">
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
        @if($featured ?? false)<span class="absolute left-4 top-4 rounded-full bg-primary px-3 py-1 text-xs font-bold text-white shadow">⭐ Featured</span>@endif
        <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
            <h3 class="text-lg font-black leading-tight">{{ $name }}</h3>
            <p class="text-sm font-medium text-white/90">{{ $dept }}</p>
        </div>
    </div>
    <div class="p-4">
        @if($member->subjects)
        <div class="flex flex-wrap gap-1.5">
            @foreach(collect($member->subjects)->take(4) as $subject)
            <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600">{{ $subject }}</span>
            @endforeach
        </div>
        @endif
        @if($bio)<p class="mt-3 line-clamp-2 text-sm leading-6 text-gray-500">{{ $bio }}</p>@endif
    </div>

    {{-- Modal content (hidden) --}}
    <div id="tm-{{ $member->id }}" class="hidden">
        <div class="relative h-56 overflow-hidden bg-gray-100">
            <img src="{{ $photo }}" alt="{{ $name }}" class="h-full w-full object-cover" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($name) }}&background=168536&color=fff&size=512&bold=true'">
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
                <h2 class="text-2xl font-black">{{ $name }}</h2>
                <p class="font-semibold text-white/90">{{ $dept }}</p>
            </div>
        </div>
        <div class="p-6">
            @if($bio)<p class="text-gray-700 leading-relaxed">{{ $bio }}</p>@endif
            @if($member->subjects)
            <div class="mt-4">
                <h4 class="text-sm font-semibold text-gray-900 mb-2">Expertise</h4>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($member->subjects as $subject)
                    <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">{{ $subject }}</span>
                    @endforeach
                </div>
            </div>
            @endif
            @if(!empty(array_filter($links)))
            <div class="mt-5 flex flex-wrap gap-3">
                @foreach($links as $platform => $url)
                    @if($url)
                    @php $icons = ['facebook'=>'fa-facebook','linkedin'=>'fa-linkedin','twitter'=>'fa-x-twitter','instagram'=>'fa-instagram','github'=>'fa-github','website'=>'fa-globe']; @endphp
                    <a href="{{ $url }}" target="_blank" rel="noopener" class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-gray-600 transition hover:bg-primary hover:text-white" title="{{ ucfirst($platform) }}">
                        <i class="fa-brands {{ $icons[$platform] ?? 'fa-link' }} text-lg"></i>
                    </a>
                    @endif
                @endforeach
            </div>
            @endif
            <a href="{{ route('contact') }}" class="mt-6 inline-flex items-center rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white transition hover:opacity-90">Connect with {{ explode(' ', $name)[0] }} →</a>
        </div>
    </div>
</article>
