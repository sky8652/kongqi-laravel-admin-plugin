<?php

namespace App\Plugin\Vote\Admin;

use App\Plugin\Vote\Models\VoteConfig;
use App\Plugin\Vote\Models\WxMerchant;
use Illuminate\Http\Request;
use App\Plugin\AdminCurlController;
use App\Plugin\Vote\Models\Vote;
use App\Plugin\Vote\Models\VoteTheme;
use Illuminate\Support\Facades\DB;

class VoteConfigController extends AdminCurlController
{
    public function setModelAddRelaction($model)
    {
        return $model->with('merchant');//关联商户
    }

    /**
     *  设置模型
     * @return WxMerchant|mixed
     */
    public function setModel()
    {
        return $this->model = new VoteConfig();
    }

    public function createEditData($show = '')
    {
        return ['wx_merchant' => WxMerchant::getAll(), 'is_prize' => [['id' => 1, 'name' => '需要'], ['id' => 0, 'name' => '无需抽奖']]];
    }

    public function checkRuleField()
    {
        return [

            'start_at' => '开始时间',
            'end_at' => '结束时间',

        ];
    }

    /**
     * JSON 列表输出项目设置
     * @param $item
     * @return mixed
     */
    public function apiJsonItemExtend($item)
    {
        $item['btns'] = [
            [
                'url' => plugin_url('Vote\Admin\VoteTheme', 'index', ['vote_config_id' => $item->id]),
                'name' => '配置主题',
                'class_name' => 'layui-btn-green'
            ]
        ];
        $item['btn_open'] = [
            [
                'url' => plugin_url('Vote\Admin\VoteConfig', 'show', ['id' => $item->id]),
                'name' => '查看结果',
                'class_name' => 'layui-btn-primary',
                'w' => '500px',
                'h' => '600px'
            ],
            [
                'url' => plugin_url('Vote\Admin\User', 'index', ['wx_merchant' => $item->wx_merchant_id, 'model_type' => 'vote', 'model_id' => $item->id]),
                'name' => '用户数据',
                'class_name' => 'layui-btn-primary',
                'w' => '100%',
                'h' => '100%'
            ],

            [
                'url' => plugin_url('Vote\Admin\ViewShow', 'index', ['token' => base64_encode(plugin_route('vote.vote.index', ['merchant' => $item->wx_merchant_id, 'token' => $item->token]))]),
                'name' => '查看',
                'class_name' => 'layui-btn-primary'
            ]
        ];
        $item['wx_merchant_name'] = $item->merchant['name'] ?? '';
        return $item;
    }

    /**
     * 表单验证规则
     * @param string $id
     * @return array
     */
    public function checkRule($id = '')
    {

        return [
            'name' => 'required',
            'start_at' => 'required',
            'end_at' => 'required',

        ];

    }

    public function showData($show)
    {

        $user_id = \request()->input('user_id');
        //获得投票主题
        //获取主题
        $theme = VoteTheme::where('vote_config_id', $show->id)->orderBy('sort', 'desc')->orderBy('id', 'asc')->get()->toArray();

        //设置$theme[2]=[items];
        $item = VoteTheme::toTtem($theme);

        //print_r($item);
        //算出投票数据
        //取得投票选项的总数total=2,item=2
        if ($user_id) {
            $result = Vote::select(DB::raw('count(*) as total,vote_item_id as id'))->where('openid', $user_id)->where('vote_config_id', $show->id)->groupBy('vote_item_id')->get()->toArray();

        } else {
            $result = Vote::select(DB::raw('count(*) as total,vote_item_id as id'))->where('vote_config_id', $show->id)->groupBy('vote_item_id')->get()->toArray();

        }

        $item_result = $this->toResult($result);

        //个个项目总得投票数
        $result2 = Vote::select(DB::raw('count(*) as total,vote_theme_id as id'))->where('vote_config_id', $show->id)->groupBy('vote_theme_id')->get()->toArray();
        $theme_result = $this->toResult($result2);
        //获得自己投票

        $data = [

            'theme' => $theme,
            'item' => $item,
            'item_result' => $item_result,
            'theme_result' => $theme_result,

        ];
        return $data;
    }

    public function toResult($data)
    {
        $arr = [];
        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                $arr[$value['id']] = $value['total'];
            }
        }
        return $arr;
    }
}
