{{--
  ホームズの10のわざを、この手帳の段（手がかり→見立て→もしそうなら→答え合わせ…）ごとに並べる（requirements.md の 3-3）。
  段ごとに絵と色を付け、初めて出る言葉（見立て・屋根裏の棚など）には一言の説明を添える。わざは押すと技能の画面へ、原作の場面はカードへ進む。
  使い方：<x-guide.flow-steps :steps="$overview['steps']" :skills="$skills" :scenes="$scenes" :reasoning-names="$reasoningNames" :kid="$kid" />
  steps：overview.json の steps（step・icon・tone・note・skills・example・scene・reasoning）。skills・scenes：キーで引ける技能・カード。
  reasoningNames：推論のキーと名前（演繹など）。kid：子ども向けの名前を先に出すか（ふだんの場面が親子の人）
--}}
@props(['steps', 'skills', 'scenes', 'reasoningNames' => [], 'kid' => false])
{{-- 段の色ごとの、絵の丸の色の組み合わせ --}}
@php($dots = ['clue' => 'bg-clue', 'hypothesis' => 'bg-hypothesis', 'prediction' => 'bg-prediction', 'answer' => 'bg-answer', 'plain' => 'bg-ink'])
<ol {{ $attributes->class(['divide-y divide-rule border-y border-rule']) }}>
  {{-- 手帳の段を1つずつ、絵・名前・一言の説明・練習できるわざ・原作の場面の順に並べる --}}
  @foreach ($steps as $step)
    <li class="flex gap-3 py-4">
      <span @class(['grid size-10 shrink-0 place-items-center rounded-full text-on-ink', $dots[$step['tone']] ?? 'bg-ink'])>
        <x-ui.step-icon :name="$step['icon']" class="size-6" />
      </span>
      <div class="min-w-0 flex-1">
        <p class="flex flex-wrap items-center gap-x-2 gap-y-1">
          <span class="font-mincho text-lg font-bold">{{ $step['step'] }}</span>
          {{-- この段で働く考え方（演繹など）が決まっていれば、札にして説明のページへつなぐ --}}
          @if ($step['reasoning'])
            <a href="{{ route('guide.reasoning') }}#{{ $step['reasoning'] }}" class="inline-flex min-h-7 items-center rounded-full border border-rule px-2.5 text-xs font-semibold text-muted no-underline hover:text-ink">{{ $reasoningNames[$step['reasoning']] ?? '' }}</a>
          @endif
        </p>
        <p class="text-sm text-muted">{{ $step['note'] }}</p>
        <ul class="mt-2 space-y-2">
          {{-- この段で練習できるわざを1つずつ、名前（親子の人は子ども向けの名前を先に）と短い説明で出す --}}
          @foreach ($step['skills'] as $key)
            <li>
              <a href="{{ route('guide.skills.show', $key) }}" class="font-bold">{{ $kid ? $skills[$key]['kid_name'] : $skills[$key]['name'] }}</a>
              <span class="text-sm text-muted">（{{ $key }}{{ $kid ? '・'.$skills[$key]['name'] : '' }}）</span>
              <p class="text-sm">{{ $skills[$key]['summary'] }}</p>
            </li>
          @endforeach
        </ul>
        <p class="mt-2 text-sm">
          <span class="text-muted">{{ config('suiri.guide.index.in_story') }}</span>
          {{-- 場面のカードがあればカードへのリンクに、なければ文字だけ出す --}}
          @if ($step['scene'] && isset($scenes[$step['scene']]))
            <a href="{{ route('guide.scenes.show', $step['scene']) }}">{{ $step['example'] }}</a>
          @else
            {{ $step['example'] }}
          @endif
        </p>
      </div>
    </li>
  @endforeach
</ol>
