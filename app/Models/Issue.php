<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    public function crowd()
    {
        return $this->belongsTo(Crowd::class);
    }


    static public function getIssue($crowd_id,$issue){

        $info = self::select('id', 'crowd_id', 'issue', 'start_time', 'end_time', 'draw', 'portion', 'total_price', 'profit', 'day', 'bought')
            ->where('crowd_id',$crowd_id)
            ->where('issue',$issue)
            ->first();

    }

}
