<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use QL\QueryList;

class Youtube extends Command
{

    protected $signature = 'youtube';

    protected $description = '采集youtube';

    private $url = 'https://www.youtube.com';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $html = QueryList::get($this->url)->getHtml();
        preg_match('/<script.*?>var ytInitialData =([\s\S]+?)<\/script>/',$html,$matches, PREG_OFFSET_CAPTURE);

        $data = json_decode(substr($matches[1][0],0,-1),true);
        $lists = $data['contents']['twoColumnBrowseResultsRenderer']['tabs'][0]['tabRenderer']['content']['richGridRenderer']['contents'];
        $rows = [];
        foreach ($lists as $item) {
            $row = [];
            if (isset($item['richItemRenderer']['content']['videoRenderer']['title']['runs'][0]['text'])) {
                $row['title'] = $item['richItemRenderer']['content']['videoRenderer']['title']['runs'][0]['text'];
                // 标题存在则跳过
                $id = DB::table('youtube')->where('title','=',$row['title'])->value('id');
                if ($id) {
                    continue;
                }
            }
            if (isset($item['richItemRenderer']['content']['videoRenderer']['videoId'])) {
                $row['link'] = sprintf('%s/watch?v=%s', $this->url, $item['richItemRenderer']['content']['videoRenderer']['videoId']);
            }
            if (isset($item['richItemRenderer']['content']['videoRenderer']['viewCountText']['simpleText'])) {
                $row['people'] = $item['richItemRenderer']['content']['videoRenderer']['viewCountText']['simpleText'];
            }
            if (isset($item['richItemRenderer']['content']['videoRenderer']['ownerText']['runs'][0]['text'])) {
                $row['author'] = $item['richItemRenderer']['content']['videoRenderer']['ownerText']['runs'][0]['text'];
            }

            $row && $row['created_at'] = $row['updated_at'] = Carbon::now();
            $row && array_push($rows,$row);
        }

        if ($rows) {
            DB::table('youtube')->insert($rows);
        }
    }
}
