<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-end">
            <x-filament::badge color="gray" size="lg">
                {{ $skillsCount }} skills
            </x-filament::badge>
        </div>

        @forelse ($categories as $category)
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-5">
                    <h1 class="text-2xl font-bold tracking-normal text-gray-950 dark:text-white">{{ $category['title'] }}</h1>
                </div>

                <div class="flex flex-wrap gap-2.5">
                    @forelse ($category['skills'] as $skill)
                        <x-filament::badge :color="$category['color']" size="lg">
                            {{ $skill }}
                        </x-filament::badge>
                    @empty
                        <span class="text-sm text-gray-500 dark:text-gray-400">No skills in this category.</span>
                    @endforelse
                </div>
            </section>
        @empty
            <section class="rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                No profile skills imported yet.
            </section>
        @endforelse
    </div>
</x-filament-panels::page>
