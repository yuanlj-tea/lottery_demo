<?php
namespace app\index\controller;

use think\Controller;
use think\Db;
use think\View;
use app\admin\controller\Articles;

class Index extends Controller
{
    protected $round_error;

    public function index1()
    {
        return $this->fetch();
    }

    public function award()
    {
        $round=(int)input('round',1);
        $round_error=$this->existsRound($round);
        if(!$round_error){
            return json(msg(-1,'',$this->round_error));
        }
        $data=$this->getAward();

        //shuffle($res);
        return json(msg(1,$data,''));
    }

    /**
     * 抽奖首页
     * @return mixed
     */
    public function index()
    {
        //echo APP_PATH.request()->module().'/view/lottery/index.html';die;
        //$view=new View();
        //return $this->fetch(APP_PATH.request()->module().'/view/index.html');
        return $this->fetch('lottery/index');
    }
    
    /**
     * 抽奖概率
     */
    public function lottery()
    {
        $round=(int)input('round',1);

        $round_error=$this->existsRound($round);
        if(!$round_error){
            return json(msg(-1,'',$this->round_error));
        }

        $data=$this->getAward();

        //每个奖品的中奖几率,奖品ID作为数组下标
        if(is_array($data) && sizeof($data)){
            foreach($data as $k=>$v){
                $item[$v['unit']]=$v['probability'];
            }
        }
        $awardUnit=$this->getAwardUnid($item);
        foreach($data as $k=>$v){
            if($v['unit']==$awardUnit){
                $awardName=$v['desc'];
                $awardId=$v['id'];
                $prizeNum=$v['prize_num'];
            }
        }
        $this->insWinning($awardId);
        return json(msg(1,$awardUnit,$awardName));
    }

    public function getAwardUnid($item)
    {
        //中奖概率基数
        $num=array_sum($item);
        foreach ($item as $k => $v) {
            //获取一个1到当前基数范围的随机数
            $rand = mt_rand(1, $num);
            if ($rand <= $v) {
                //假设当前奖项$k=2,$v<=5才能中奖
                $res = $k;
                break;
            } else {
                //假设当前奖项$k=6,$v>1900,则没中六等奖,总获奖基数2000-1900,前五次循环都没中则2000-1-5-10-24-60=1900,必中6等奖
                $num -= $v;
            }
        }
        return $res;
    }

    /**
     * 获取抽奖的奖项
     * @param int $round
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getAward($round=1)
    {
        $res=Db::table('snake_award')->where('rounds',1)->select();
        $unit=[0,1,2,7,3,6,5,4];
        if(count($res)<8){
            $padding=[
                'id'=>'',
                'name'=>'谢谢参与',
                'probability'=>0,
                'desc'=>'谢谢参与',
                'addtime'=>'',
                'prize_num'=>7,
                'rounds'=>isset($res[0]['rounds']) && !empty($res[0]['rounds']) ? $res[0]['rounds'] : 1,
            ];
            while(count($res)<8){
                array_push($res,$padding);
            }
        }
        foreach($res as $k=>&$v){
            $v['unit']=$unit[$k];
        }
        return $res;
    }

    /**
     * 判断轮次是否存在
     * @param int $round
     * @return bool
     */
    public function existsRound($round=1)
    {
        $round=Db::table('snake_award')->where('rounds',$round)->find();

        if(empty($round)){
            $this->round_error='不存在的抽奖轮次!';
            return false;
        }
        return true;
    }

    /**
     * 插入中奖信息
     * @param $award_id
     */
    public function insWinning($award_id)
    {
        $award_info=Db::table('snake_award')->where('id',$award_id)->find();
        if(!empty($award_info) && isset($award_info['id'])){
            $data['award_id']=$award_id;
            $data['datetime']=date('Y-m-d H:i:s');
            Db::table('snake_winning')->insert($data);
        }

    }
}
