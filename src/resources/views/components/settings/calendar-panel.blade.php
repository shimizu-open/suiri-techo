{{--
  設定の画面の「Google カレンダー」の枠。いつも「つなぐと、確かめる日をカレンダーが知らせてくれるよ」の一言から始める。
  状態は4つ：準備中（Google の鍵が未設定）・つないでいない・つながっている・切れた（札の代わりに赤い一文「連携が切れました。つなぎ直してください。」）。
  つないでいない → 「カレンダーとつなぐ」（説明の画面へ）。つながっている → 最後に同期した日時と「つなぐのをやめる」。
  切れた → 「つなぎ直す」と「つなぐのをやめる」。やめるときは、専用カレンダーも Google から消すかを選べる（つながっているときだけ）。
  枠で囲まず、上の罫線で区切る。文は config('suiri.settings.calendar')
  使い方：<x-settings.calendar-panel :connection="$user->calendarConnection" />
  connection：その人の CalendarConnection（つないでいなければ null）。ready：Google の鍵があるか（省略すると config から決める）
--}}
@props(['connection' => null, 'user' => null, 'ready' => \App\Services\Google\GoogleTokens::configured()])
@php($c = config('suiri.settings.calendar'))
@php($name = config('suiri.calendar.name'))
<section aria-labelledby="calendar-panel-title" {{ $attributes->class(['border-t border-rule pt-4']) }}>
  <div class="flex items-start gap-3">
    <span class="grid size-11 shrink-0 place-items-center rounded-full bg-prediction-soft text-prediction"><x-ui.step-icon name="calendar" class="size-6" /></span>
    <div class="min-w-0 flex-1">
      <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
        <h2 id="calendar-panel-title" class="text-base">{{ $c['title'] }}</h2>
        {{-- 状態の札：つながっている（緑）・つないでいない・準備中。切れたときは札を出さず、下の赤い一文で知らせる（同じ言葉を2回出さない） --}}
        @if ($connection?->isActive())
          <x-ui.badge tone="ok">{{ $connection->status->label() }}</x-ui.badge>
        @elseif ($connection === null)
          <x-ui.badge>{{ $ready ? 'つないでいません' : '準備中' }}</x-ui.badge>
        @endif
      </div>
      <p class="mt-1 text-base">{{ $c['lead'] }}</p>
    </div>
  </div>

  {{-- 状態ごとに、説明と押せるボタンを変える --}}
  @if ($connection === null && ! $ready)
    <p class="mt-3 text-sm text-muted">{{ $c['not_ready'] }}</p>
  @elseif ($connection === null)
    <p class="mt-3 text-sm leading-relaxed text-muted">{{ strtr($c['what'], [':name' => $name]) }}</p>
    <x-ui.button variant="secondary" :href="route('calendar.connect')" class="mt-3 w-full sm:w-auto"><x-ui.icon name="calendar" class="size-5" />{{ $c['connect'] }}</x-ui.button>
  @elseif ($connection->isBroken())
    <p class="mt-3 text-base font-bold text-accent">{{ $c['broken'] }}</p>
    <p class="mt-1 text-sm leading-relaxed text-muted">{{ $c['broken_why'] }}</p>
    <div class="mt-3 flex flex-wrap items-start gap-3">
      <x-ui.button :href="route('calendar.connect')">{{ $c['reconnect'] }}</x-ui.button>
      <x-ui.confirm :action="route('calendar.disconnect')" :label="$c['stop']" :confirmLabel="$c['stop_confirm']" :message="$c['stop_broken']" />
    </div>
  @else
    <p class="mt-3 text-sm leading-relaxed text-muted">{{ strtr($c['active'], [':name' => $name]) }}</p>
    <p class="mt-2 text-sm">
      <span class="text-muted">{{ $c['synced'] }}</span>
      <span class="font-bold">{{ $connection->last_synced_at?->format('Y年n月j日 H:i') ?? $c['not_synced'] }}</span>
    </p>
    <x-ui.confirm :action="route('calendar.disconnect')" :label="$c['stop']" :confirmLabel="$c['stop_confirm']" class="mt-2" :message="$c['stop_active']">
      <label class="mb-3 flex items-start gap-2">
        <input type="checkbox" name="delete_calendar" value="1" class="mt-1 size-5 shrink-0 accent-[var(--accent)]">
        <span>{{ strtr($c['delete_calendar'], [':name' => $name]) }}</span>
      </label>
    </x-ui.confirm>
  @endif
</section>
