{{--
  名推理カードの作品名と章（『緋色の研究』A Study in Scarlet 第1部第1・2章）。英語の題は斜体にする。
  使い方：<x-guide.work :scene="$scene" />
  scene：scenes.json の1件（story_ja・story_en・chapter を使う）
--}}
@props(['scene'])
<span {{ $attributes }}>『{{ $scene['story_ja'] }}』<i lang="en">{{ $scene['story_en'] }}</i> {{ $scene['chapter'] }}</span>
