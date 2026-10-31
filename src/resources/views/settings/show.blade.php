{{--
  設定の画面（A-4〜A-6）。上から：あなたの名前 → 一緒に使う人（相棒）→ どんな場面で使う？ → いつ書く？ を1つずつ問いの形で並べ、
  横いっぱいの「保存する」で送る → カレンダーの枠 → ChatGPT・Claude とつなぐ枠（どちらも外とつながるものなので並べる）→
  ログインしているアカウント → データのダウンロードと退会（危ない操作なので一番下にたたむ）。
  4つの問いは順番のあるものではないので番号は付けず、罫線で1つずつ区切る（枠のカードでは囲まない）
--}}
@php($s = config('suiri.settings'))
<x-layouts.app title="設定">
  <form method="POST" action="{{ route('settings.update') }}">
    @csrf
    @method('PATCH')
    <div class="divide-y divide-rule border-y border-rule">
      <div class="py-4">
        <x-ui.field name="name" :label="$s['name']['label']" :value="$user->name" required :maxlength="config('suiri.limits.user_name')" autocomplete="nickname" :hint="$s['name']['hint']" />
      </div>
      <div class="py-4">
        <x-ui.field name="partner_name" :label="$s['partner']['label']" :value="$user->partner_name" :maxlength="config('suiri.limits.partner_name')"
          :placeholder="$s['partner']['placeholder']" :hint="$s['partner']['hint']" />
      </div>
      <div class="py-4">
        <x-ui.choices name="default_scene" :label="$s['scene']['label']" :options="$scenes" :selected="$user->default_scene" :hint="$s['scene']['hint']" />
      </div>
      <div class="py-4">
        <x-ui.field name="cue" :label="$s['cue']['label']" :value="$user->cue" :maxlength="config('suiri.limits.cue')" list="cue-examples"
          placeholder="例：{{ $cueExamples[0] ?? '' }}" :hint="$s['cue']['hint']" />
        <datalist id="cue-examples">
          {{-- きっかけの例文を1つずつ候補にする --}}
          @foreach ($cueExamples as $example)
            <option value="{{ $example }}"></option>
          @endforeach
        </datalist>
      </div>
    </div>
    <x-ui.big-button icon="check" class="mt-4">{{ $s['save'] }}</x-ui.big-button>
  </form>

  <x-settings.calendar-panel :user="$user" :connection="$user->calendarConnection" class="mt-8" />
  <x-settings.ai-panel :apps="$aiApps" class="mt-8" />
  <x-settings.account :user="$user" class="mt-8" />
</x-layouts.app>
