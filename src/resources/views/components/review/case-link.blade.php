{{--
  事件のなぞ（題名）を、事件の画面へのリンクにして出す。事件の画面（ルート cases.show）がまだなければ文字だけにする。
  使い方：<x-review.case-link :case="$note->caseFile" />。case：CaseFile（null なら何も出さない）
--}}
@props(['case' => null])
{{-- 事件があって事件の画面もあればリンク、画面がなければ文字だけ、事件がなければ何も出さない --}}
@if ($case && Route::has('cases.show'))
  <a href="{{ route('cases.show', $case) }}" {{ $attributes }}>{{ $case->title }}</a>
@elseif ($case)
  <span {{ $attributes }}>{{ $case->title }}</span>
@endif
