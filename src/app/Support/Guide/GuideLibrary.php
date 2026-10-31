<?php

namespace App\Support\Guide;

use Illuminate\Support\Facades\Cache;

/**
 * 手引きの教材（技能・名推理カード・練習メニュー・推論の説明・入口の案内）を database/data/guide/*.json から読む係。
 * 教材は利用者のデータではなく「本の中身」なので、表ではなくファイルに置いている。読んだ中身はキャッシュする
 */
class GuideLibrary
{
    /** 10の技能（S1〜S10）を、ファイルの順のまま返す */
    public function skills(): array
    {
        return $this->load('skills');
    }

    /** キーで技能を1つ返す。ないキーなら null */
    public function skill(string $key): ?array
    {
        return $this->find('skills', $key);
    }

    /** 名推理カードを、ファイルの順のまま返す */
    public function scenes(): array
    {
        return $this->load('scenes');
    }

    /** キーで名推理カードを1つ返す。ないキーなら null */
    public function scene(string $key): ?array
    {
        return $this->find('scenes', $key);
    }

    /** 練習メニューを、ファイルの順のまま返す */
    public function practices(): array
    {
        return $this->load('practices');
    }

    /** キーで練習メニューを1つ返す。ないキーなら null */
    public function practice(string $key): ?array
    {
        return $this->find('practices', $key);
    }

    /** 演繹・帰納・アブダクションの説明（intro・kid_intro・items・note・kid_note）を返す */
    public function reasoning(): array
    {
        return $this->load('reasoning');
    }

    /** 手引きの入口の中身（はじめの説明・入口の案内・流れと技能の対応・外した場面のひとこと）を返す */
    public function overview(): array
    {
        return $this->load('overview');
    }

    /** その技能を練習できる練習メニューを、ファイルの順で返す */
    public function practicesFor(string $skillKey): array
    {
        return array_values(array_filter($this->practices(), fn (array $practice) => $practice['skill'] === $skillKey));
    }

    /** その技能を練習できる名推理カードを、ファイルの順で返す */
    public function scenesFor(string $skillKey): array
    {
        return array_values(array_filter($this->scenes(), fn (array $scene) => in_array($skillKey, $scene['skills'], true)));
    }

    /**
     * 次に遊ぶ名推理カードを返す：まだ推理を書いていないカードのうち並びの最初の1枚。全部書いていたら最初の1枚に戻る。
     * 手引きの入口とカードの一覧の「このカードで遊ぶ」に使う。カードが1枚もなければ null
     */
    public function nextScene(array $doneKeys): ?array
    {
        $scenes = $this->scenes();

        return collect($scenes)->first(fn (array $scene) => ! in_array($scene['key'], $doneKeys, true)) ?? ($scenes[0] ?? null);
    }

    /** 技能のキーと名前の対応（S1 => 見る→観察する）を返す。カードや練習メニューの札に技能の名前を出すときに使う */
    public function skillNames(): array
    {
        return array_column($this->skills(), 'name', 'key');
    }

    /**
     * 並び（skills・scenes など）の中で、そのキーの1つ前と1つ後ろを返す。端なら null。
     * 技能や名推理カードの「前へ・次へ」に使う
     *
     * @return array{prev: ?array, next: ?array}
     */
    public function neighbors(string $name, string $key): array
    {
        $items = $this->load($name);
        $index = array_search($key, array_column($items, 'key'), true);

        // 並びにないキーなら、前も後ろもない
        if ($index === false) {
            return ['prev' => null, 'next' => null];
        }

        return ['prev' => $items[$index - 1] ?? null, 'next' => $items[$index + 1] ?? null];
    }

    /** 並び（skills・scenes など）の中で、そのキーが何番目か（1から数える）を返す。カードの「No.07」に使う。並びにないキーなら null */
    public function position(string $name, string $key): ?int
    {
        $index = array_search($key, array_column($this->load($name), 'key'), true);

        return $index === false ? null : $index + 1;
    }

    /** 配列の中から key が一致する1件を探す */
    private function find(string $name, string $key): ?array
    {
        return collect($this->load($name))->firstWhere('key', $key);
    }

    /** JSON を読んで配列にする。ファイルが変わったら読み直すよう、更新日時をキャッシュのキーに入れる */
    private function load(string $name): array
    {
        $path = database_path("data/guide/{$name}.json");

        return Cache::rememberForever(
            "guide:{$name}:".filemtime($path),
            fn () => json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR),
        );
    }
}
