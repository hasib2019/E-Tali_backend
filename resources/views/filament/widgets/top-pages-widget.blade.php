<x-filament-widgets::widget>
    <div class="grid gap-6 md:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Most-used screens (30 days)</x-slot>

            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="py-1">Screen</th>
                        <th class="py-1 text-right">Views</th>
                        <th class="py-1 text-right">Users</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->getTopPages() as $row)
                        <tr class="border-t border-gray-100 dark:border-white/10">
                            <td class="py-1 font-medium">{{ $row['screen'] }}</td>
                            <td class="py-1 text-right tabular-nums">{{ number_format($row['views']) }}</td>
                            <td class="py-1 text-right tabular-nums">{{ number_format($row['users']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-2 text-gray-500">No activity recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Recently active users</x-slot>

            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="py-1">User</th>
                        <th class="py-1">Last screen</th>
                        <th class="py-1 text-right">Active</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->getRecentUsers() as $u)
                        <tr class="border-t border-gray-100 dark:border-white/10">
                            <td class="py-1">
                                <div class="font-medium">{{ $u['name'] }}</div>
                                <div class="text-xs text-gray-500">{{ $u['email'] }}</div>
                            </td>
                            <td class="py-1">{{ $u['screen'] ?? '—' }}</td>
                            <td class="py-1 text-right whitespace-nowrap">{{ $u['active'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-2 text-gray-500">No active users yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
