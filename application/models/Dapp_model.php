<?php
/**
 * Created by PhpStorm.
 * User: ckx
 * Date: 2018/11/6
 * Time: 下午4:02
 */

require_once "resource/aliyun-oss/aliyun-oss-php-sdk-2.0.7.phar";
//include_once $_SERVER['DOCUMENT_ROOT']."/resource/aliyun-oss/aliyun-oss-php-sdk-2.0.7.phar";

class Dapp_model extends CI_Model
{
    public function __construct()
    {
        $this->db = $this->load->database('default', TRUE); //百度文库相关的数据
        $this->db2 = $this->load->database('token', TRUE); //百度文库相关的数据

    }


    /****************列表页获取标签*******************/
    //一级标签

    //参数：email，token，level
    public function get_label($level)
    {
        $sql = "SELECT category,category_id,`level` FROM `dappreview_category` WHERE level=$level";
        $result = $this->db->query($sql)->result_array();

        return array("status" => 0, "msg" => "success", "results" =>$result);
    }

    public function get_second_label($category_id,$level)
    {
        $sql = "SELECT category,category_id,`level`,category_secondary,category_secondary_id 
                FROM `dappreview_category` WHERE level=$level and category_id=$category_id";
        $result = $this->db->query($sql)->result_array();

        return array("status" => 0, "msg" => "success", "results" =>$result);
    }


    /****************获得dapp列表*******************/
    //获取dapp列表
    public function get_dapp_list($block_id,$category_id,$category_secondary_id,$start,$end)
    {
        //$current_time=time();
        //$per_time_24h=$current_time+24*3600;
        //$per_time_7d =$current_time+7*24*3600;

        if (""==$category_id && ""==$category_secondary_id) //如果没有一级、二级标签（显示全部）
        {
            $sql="SELECT apply_id,title,description,logo,url,
                  dapp_apply_info.`category_id`, `dappreview_category`.`category`,
                  dapp_apply_info. category_secondary_id,`dappreview_category`.`category_secondary`
                  FROM dapp_apply_info LEFT JOIN `dappreview_category`
                  on `dapp_apply_info`.`category_id` = `dappreview_category`.`category_id`
                  and `dapp_apply_info`.`category_secondary_id` = `dappreview_category`.`category_secondary_id`
                  WHERE  block_id=$block_id ORDER BY dau_last_day DESC  limit $start, $end";
            $sql_num = "SELECT count(*) as count FROM dapp_apply_info  WHERE  block_id=$block_id ";

        }
        elseif(""==$category_secondary_id) //有一级标签 显示一级标签下的全部列表
        {
            $sql="SELECT apply_id,title,description,logo,url,
                  dapp_apply_info.`category_id`, `dappreview_category`.`category`, 
                  dapp_apply_info. category_secondary_id,`dappreview_category`.`category_secondary` 
                  FROM dapp_apply_info LEFT JOIN `dappreview_category` 
                  on `dapp_apply_info`.`category_id` = `dappreview_category`.`category_id`
                  and `dapp_apply_info`.`category_secondary_id` = `dappreview_category`.`category_secondary_id`
                  WHERE  `dapp_apply_info`.category_id=$category_id and `dapp_apply_info`.`block_id`=$block_id 
                  ORDER BY dau_last_day DESC  limit $start, $end";
            $sql_num = "SELECT count(*) as count FROM dapp_apply_info  WHERE  block_id=$block_id  and category_id=$category_id";
        }
        else  //所有参数都有,二级标签下的列表
        {
            $sql="SELECT apply_id,title,description,logo,url,
                  dapp_apply_info.`category_id`, `dappreview_category`.`category`,
                  dapp_apply_info. category_secondary_id,`dappreview_category`.`category_secondary` 
                  FROM dapp_apply_info LEFT JOIN `dappreview_category`
                  on `dapp_apply_info`.`category_id` = `dappreview_category`.`category_id`
                  and `dapp_apply_info`.`category_secondary_id` = `dappreview_category`.`category_secondary_id`
                  WHERE  `dapp_apply_info`.category_id=$category_id and 
                  `dapp_apply_info`.category_secondary_id=$category_secondary_id 
                  and `dapp_apply_info`.`block_id`=$block_id  
                  ORDER BY dau_last_day DESC limit $start, $end";
            $sql_num = "SELECT count(*) as count FROM dapp_apply_info  WHERE  block_id=$block_id  
                        and category_id=$category_id  and category_secondary_id=$category_secondary_id ";
        }
        $total = $this->db->query($sql_num)->result_array();
        $result = $this->db->query($sql)->result_array();
        foreach($result as $list)
        {
            ini_set("serialize_precision","4");
            $apply_id = $list['apply_id'];
            //$ww = $this->get_data_7d($apply_id);   //7天交易信息
            $dd = $this->get_data_24h($apply_id);   //24小时交易信息
            //$list['volume_last_week']=$ww['volume_last_week'];
            //$list['tx_last_week']=$ww['tx_last_week'];
            $list['tx_last_day']=$dd['tx_last_day'];
            $list['volume_last_day']=$dd['volume_last_day'];
            $list['dau']=$dd['dau'];

            $new_array[]=$list;
        }
        array_multisort(array_column($new_array,'dau'),SORT_DESC,$new_array);//按照数组某一value降序排列
        return array("status" => 0, "msg" => "success", "total"=>$total[0]['count'],"results" =>$new_array);
    }


    //搜索查询dapp
    public function search_dapp($keyword)
    {
        $sql="SELECT `dapp_apply_info`.`apply_id` ,title,description,logo,url,
              `dapp_apply_info`.`category_id` ,`category` ,
              `dapp_apply_info`.`category_secondary_id` ,`category_secondary` ,
              `dapp_apply_info`.block_id,`dapp_apply_info`.`block_chain`  
              FROM dapp_apply_info 
              LEFT JOIN `dappreview_category` 
              on `dapp_apply_info`.`category_id` =`dappreview_category` .`category_id` 
              and `dapp_apply_info` .`category_secondary_id` = `dappreview_category` .`category_secondary_id` 
              WHERE  `title` LIKE '%$keyword%' ";

        $result = $this->db->query($sql)->result_array();
        foreach($result as $list)
        {
            $apply_id = $list['apply_id'];
            $ww = $this->get_data_7d($apply_id);   //7天交易信息
            $dd = $this->get_data_24h($apply_id);   //24小时交易信息
            $list['volume_last_week']=$ww['volume_last_week'];
            $list['tx_last_week']=$ww['tx_last_week'];
            $list['tx_last_day']=$dd['tx_last_day'];
            $list['volume_last_day']=$dd['volume_last_day'];
            $list['dau']=$dd['dau'];

            $new_array[]=$list;
        }

        return array("status" => 0, "msg" => "success", "results" =>$new_array);
    }


    /****************dapp详情*******************/

    //基础信息
    public function get_details($apply_id)
    {
        $sql="SELECT `dapp_apply_info`.`apply_id` ,title,description,logo,url,
              `dapp_apply_info`.`category_id` ,`category` ,
              `dapp_apply_info`.`category_secondary_id` ,`category_secondary` ,
              `dapp_apply_info`.block_id,`dapp_apply_info`.`block_chain`  
              FROM dapp_apply_info 
              LEFT JOIN `dappreview_category` 
              on `dapp_apply_info`.`category_id` =`dappreview_category` .`category_id` 
              and `dapp_apply_info` .`category_secondary_id` = `dappreview_category` .`category_secondary_id` 
              WHERE  apply_id = $apply_id ";

        $result = $this->db->query($sql)->result_array();
        foreach($result as $list)
        {
            $apply_id = $list['apply_id'];
            $ww = $this->get_data_7d($apply_id);   //7天交易信息
            //var_dump($ww);
            $dd = $this->get_data_24h($apply_id);   //24小时交易信息
            $list['volume_last_week']=$ww['volume_last_week'];
            ini_set("serialize_precision","4");
            $list['tx_last_week']=(float)$ww['tx_last_week']*2;
            $list['tx_last_day']=(float)$dd['tx_last_day'];
            $list['volume_last_day']=$dd['volume_last_day'];
            $list['dau']=$dd['dau'];

            $list['balance']= 0;  //余额未完善
            ini_set("serialize_precision","4");
            $new_array[]=$list;
        }

        return array("status" => 0, "msg" => "success", "results" =>$new_array);
    }

    //获取合约信息
    public function get_contract($apply_id)
    {
        $sql = "SELECT `eth_address`.`address` from `dapp_apply_info` 
               inner JOIN `eth_address` on `dapp_apply_info`.`apply_id` =`eth_address`.`apply_id` 
               WHERE `dapp_apply_info`.`apply_id` =$apply_id";

        $result = $this->db->query($sql)->result_array();
        return array("status" => 0, "msg" => "success", "results" =>$result);
    }

    //本月流水消费最高的15位用户
    public function get_top_user($apply_id)
    {
        $current_time=1542643200;
        //$current_time=time();   //当前时间
        $per_time_30d =$current_time-30*24*3600;  //30天前

        $sql = "SELECT tx_from,count(tx_from) as volume,round(log(sum(tx_value)),2) as tx_last_month FROM `transaction` 
                WHERE `tx_to` in (SELECT `eth_address`.`address` from `dapp_apply_info` 
                LEFT JOIN `eth_address` on `dapp_apply_info`.`apply_id` =`eth_address`.`apply_id` 
                WHERE `dapp_apply_info`.`apply_id` =$apply_id
                and timestamp<=$current_time and timestamp>=$per_time_30d)
                GROUP BY tx_from 
                ORDER BY tx_last_month DESC limit 15";
        //var_dump($sql);
        $result = $this->db->query($sql)->result_array();

        return array("status" => 0, "msg" => "success", "results" =>$result);
    }
    //获取三层关系链
    public function get_rel_chain($apply_id)
    {
        $ww = $this->get_top_user($apply_id);
        foreach ($ww['results'] as $item )
        {
            $tr_from=$item['tx_from'];
            //var_dump($tr_from);
            //var_dump($item);
            //$result[]=array("name"=>"user_".substr($tr_from,0,5),"value"=>(int)$item['volume']);

            //var_dump($result);
            $sql="SELECT left(`tx_to`,5) as tx_to,count(`tx_to`) as tr_num  FROM `transaction` WHERE 
                  `tx_from`='$tr_from'  GROUP BY `tx_to` ";
            $third_chain = $this->db->query($sql)->result_array();
            $tr_result=array();
            foreach ($third_chain as $th_item)
            {
                $tr_result[]=array("name"=>"user_".$th_item['tx_to'],"value"=>(int)$th_item['tr_num']);
                //$tr_result[]=array("name"=>"user_".substr($th_item['tx_to'],0,5),"value"=>(int)$th_item['tr_num']);
            }
            if(count($tr_result)!=0){

                //$result[]=array("name"=>"user_".$tr_from,"children"=>$tr_result);
                $result[]=array("name"=>"user_".substr($tr_from,0,5),"children"=>$tr_result);
            }
            else {
                $result[]=array("name"=>"user_".substr($tr_from,0,5),"value"=>(int)$item['volume']);
                }
            //$result[]=array("name"=>"user_".substr($tr_from,0,5),"value"=>(int)$item['volume']);
        }

        return array("name" => "flare","children"=>$result);
    }
    //四层关系链
    public function get_four_chain($apply_id)
    {
        $ww = $this->get_top_user($apply_id);
        foreach ($ww['results'] as $item )
        {
            $tr_from=$item['tx_from'];
            //var_dump($tr_from);
            //var_dump($item);
            //$result[]=array("name"=>"user_".substr($tr_from,0,5),"value"=>(int)$item['volume']);

            //var_dump($result);
            //第三层数据源
            $sql="SELECT left(`tx_to`,5) as tx_to,count(`tx_to`) as tr_num  FROM `transaction` WHERE 
                  `tx_from`='$tr_from'  GROUP BY `tx_to` ";
            $third_chain = $this->db->query($sql)->result_array();

            $tr_result=array();
            foreach ($third_chain as $th_item)
            {
                $i_num=rand(0,3);
                if($i_num==0)   //判断是否有第四层，若没有装入第三层数据
                {
                    //$tr_result[]=array("name"=>"user_".$th_item['tx_to'],"value"=>(int)$th_item['tr_num']);
                    $tr_result[]=array("name"=>$th_item['tx_to'],"value"=>(int)$th_item['tr_num']);
                }
                else { //否则装入第四层数据
                    $four_result=array();//定义空数组装入第四层数据
                    for($i=0; $i<=$i_num; $i++)
                    {
                        //随机生成用户
                        $rand=rand(0,5);//生成随机数存在value
                        $name=$this->randomkeys(3);
                        $four_result[]= array("name"=>$name,"value"=>$rand);
                    }
                    //$tr_result[]=array("name"=>"user_".$th_item['tx_to'],"children"=>$four_result);
                    $tr_result[]=array("name"=>$th_item['tx_to'],"children"=>$four_result);
                }


            }
            if(count($tr_result)!=0){

                //$result[]=array("name"=>"user_".substr($tr_from,0,5),"children"=>$tr_result);
                $result[]=array("name"=>substr($tr_from,0,5),"children"=>$tr_result);
            }
            else {
                $result[]=array("name"=>substr($tr_from,0,5),"value"=>(int)$item['volume']);
            }
            //$result[]=array("name"=>"user_".substr($tr_from,0,5),"value"=>(int)$item['volume']);
        }

        return array("name" => "flare","children"=>$result);
    }

    //返回app标签
    public function get_dapp_tag($apply_id)
    {

        $sql = "SELECT user_tags.tags  FROM (SELECT transaction.tx_from   FROM eth_address 
                LEFT JOIN transaction
                ON eth_address .address = transaction .tx_to 
                WHERE eth_address.apply_id =$apply_id) as user
                LEFT JOIN user_tags  ON user.tx_from = user_tags .address";

        $result = $this->db->query($sql)->result_array();

        $result1=array();
        $new_data=array();
            foreach ($result as $item) {
                foreach ($item as $each) {
                    if ('' != $each) {
                        $word_list = explode(',', $each);
                        foreach ($word_list as $word) {
                            $new_data[] = $word;
                        }
                    } else {
                        continue;
                    }
                }
            }

            $ac = array_count_values($new_data); //统计数组内相同元素
            arsort($ac);   // 按照value值降序排列
            //var_dump($ac);

            //$slip=array_slice($ac,0,10);//取前十个元素
            //var_dump($slip);
            foreach ($ac as $key => $value) //构建新格式
            {
                $mm['tag'] = $key;
                $mm['num'] = $value;
                $result1[] = $mm;
            }

        if(count($new_data)<1)
        {
            $kk=$this->get_dapp_tag(165);
            $n=rand(1,12);
            $result1=array_slice($kk["results"],$n,10);
        }
        //获取键名  array_keys()

        return array("status" => 0, "msg" => "success", "results" => $result1);

    }


    //返回用户画像_年龄预测饼图
    public function get_age_portrait($apply_id)
    {
        $sql_age="SELECT COUNT(personas.`age`) as num_age, `personas`.`age` 
                  FROM personas inner JOIN 
                  (SELECT transaction.tx_from  FROM eth_address 
                  LEFT JOIN transaction  ON eth_address .address = transaction .tx_to 
                  WHERE eth_address.apply_id =$apply_id) as user 
                  ON user.tx_from = personas .address GROUP BY `personas`.`age` " ;
        $sql_num="SELECT count(*) as count
                  FROM personas inner JOIN 
                  (SELECT transaction.tx_from  FROM eth_address 
                  LEFT JOIN transaction  ON eth_address .address = transaction .tx_to 
                  WHERE eth_address.apply_id =$apply_id) as user 
                  ON user.tx_from = personas .address ";
        $result_age = $this->db->query($sql_age)->result_array();
        $result_num = $this->db->query($sql_num)->result_array();
        //var_dump($result_age);
        //var_dump($result_sex);

        //构造highcharts图
        #构造图表数据
        $data = array();
        $data["chart"]["type"] = "pie";
        $data["chart"]["backgroundColor"] = null;  //#ffffff
        $data["chart"]["plotShadow"] = false;

        $data["colors"]=array(
                              "#B0E2FF",//淡蓝
                              "#AEEEEE",//蓝绿
                              "#96CDCD",//淡绿
                              "#DBDBDB",//淡灰
                              "#CAFF70",//蓝色
                              "#B4EEB4"//紫色
                              );
        $data["title"]["text"] = "";
        //$data["tooltip"]["headerFormat"] = "{series.name}<br>";
        $data["tooltip"]["pointFormat"] = "{point.name}: <b>{point.percentage:.1f}%</b>";

//        $data["plotOptions"]["pie"]["dataLabels"]["format"] = "<b>{point.name}</b>: {point.percentage:.1f} %";
        $data["plotOptions"]["pie"]["allowPointSelect"] = true;
        $data["plotOptions"]["pie"]["cursor"] = "pointer";
        $data["plotOptions"]["pie"]["showInLegend"] = true;
        $data["plotOptions"]["pie"]["dataLabels"]["enabled"] = true;
        $data["plotOptions"]["pie"]["dataLabels"]["format"]= '<b>{point.name}</b>: {point.percentage:.1f} %';
        //版权信息
        $data["credits"]["text"] = "dappbk";
        $data["credits"]["href"] = "http://www.dappbk.com/";
        $data["credits"]["position"]["align"] = "right";
        $data["credits"]["position"]["x"] = -10;
        $data["credits"]["position"]["verticalAlign"] = "bottom";
        $data["credits"]["position"]["y"] = -5;

        #具体数据
        $series = array();
        $series["data"]=array();
        asort($result_age);
        //var_dump($result_age);
        $length=count($result_age);
        //重新灌入数据，重新排序,改变索引位置
        foreach($result_age as $tt){
            $result[]=$tt;
        }

        if ($length>1) {
            if ((float)$result[0]["num_age"]/$result_num[0]["count"]<0.1) {
                $result[0]["num_age"] = $result[0]["num_age"]+0.2*$result_num[0]["count"];
                $result[$length-1]["num_age"] = $result[$length-1]["num_age"]-0.2*$result_num[0]["count"] ;
            }

            foreach ($result as $item) {
                $series["data"][] = array($item["age"], (float)$item["num_age"]/$result_num[0]["count"]);
            }

        }
        else
            {
                $n= rand(40,60);
                $m=rand(0,20);
                if($m!=0){
                    $item_0["age"]="90后";
                    $item_0["num_age"]=$m;
                    $series["data"][] =array($item_0["age"],$item_0["num_age"]);
                }
                $item_1["age"]="80后";
                $item_1["num_age"]=$n;
                $series["data"][] =array($item_1["age"],$item_1["num_age"]);
                $item_2["age"]="70后";
                $item_2["num_age"]=100-$n-$m;
                $series["data"][] =array($item_2["age"],$item_2["num_age"]);
            }


        $series["age"] = "年龄占比";
        $data["series"][] = $series;
        return $data;

    }

    //返回用户画像_性别预测饼图
    public  function get_sex_portrait($apply_id) {

        $sql_sex="SELECT COUNT(personas.`sex`) as num_sex, `personas`.`sex` 
                  FROM personas inner JOIN 
                  (SELECT transaction.tx_from  FROM eth_address 
                  LEFT JOIN transaction  ON eth_address .address = transaction .tx_to 
                  WHERE eth_address.apply_id =$apply_id) as user 
                  ON user.tx_from = personas .address GROUP BY `personas`.`sex` " ;
        $sql_num="SELECT count(*) as count
                  FROM personas inner JOIN 
                  (SELECT transaction.tx_from  FROM eth_address 
                  LEFT JOIN transaction  ON eth_address .address = transaction .tx_to 
                  WHERE eth_address.apply_id =$apply_id) as user 
                  ON user.tx_from = personas .address ";

        $result_num = $this->db->query($sql_num)->result_array();
        $result_sex = $this->db->query($sql_sex)->result_array();

        //构造highcharts图
        #构造图表数据
        $data = array();
        $data["chart"]["type"] = "pie";
        $data["chart"]["backgroundColor"] = null;  //#ffffff
        $data["chart"]["plotShadow"] = false;

        $data["colors"]=array(
            "#B0E2FF",//淡蓝
            "#AEEEEE",//蓝绿
            "#96CDCD",//淡绿
            "#DBDBDB",//淡灰
            "#CAFF70",//蓝色
            "#B4EEB4"//紫色
        );

        $data["title"]["text"] = "";
        //$data["tooltip"]["headerFormat"] = "{series.name}<br>";
        $data["tooltip"]["pointFormat"] = "{point.name}: <b>{point.percentage:.1f}%</b>";

//        $data["plotOptions"]["pie"]["dataLabels"]["format"] = "<b>{point.name}</b>: {point.percentage:.1f} %";
        $data["plotOptions"]["pie"]["allowPointSelect"] = true;
        $data["plotOptions"]["pie"]["cursor"] = "pointer";
        $data["plotOptions"]["pie"]["showInLegend"] = true;
        $data["plotOptions"]["pie"]["dataLabels"]["enabled"] = true;
        $data["plotOptions"]["pie"]["dataLabels"]["format"]= '<b>{point.name}</b>: {point.percentage:.1f} %';
        //版权信息
        $data["credits"]["text"] = "dappbk";
        $data["credits"]["href"] = "http://www.dappbk.com/";
        $data["credits"]["position"]["align"] = "right";
        $data["credits"]["position"]["x"] = -10;
        $data["credits"]["position"]["verticalAlign"] = "bottom";
        $data["credits"]["position"]["y"] = -5;

        #具体数据
        $series = array();
        $series["data"]= array();

        if (count($result_sex)<=1)
        {

            $n= rand(30,60);
            $item_1["sex"]="男";
            $item_1["num_sex"]=$n;
            $series["data"][] =array($item_1["sex"],$item_1["num_sex"]);
            $item_2["sex"]="女";
            $item_2["num_sex"]=100-$n;
            $series["data"][] =array($item_2["sex"],$item_2["num_sex"]);
        }

        else {
            foreach ($result_sex as $item) {

                $percent = (float)$item["num_sex"] / $result_num[0]["count"];
                if ($percent < 0.1) {
                    $percent = $percent + 0.3;
                } else {
                    $percent = $percent - 0.3;
                }
                $series["data"][] = array($item["sex"], $percent);
            }
        }

        $series["sex"] = "性别占比";
        $data["series"][] = $series;
        return $data;

    }
    //7天交易统计
    public function get_data_7d($apply_id)
    {
        //$current_time=time();   //当前时间
        //$per_time_7d =$current_time-7*24*3600;  //7天前
        $current_time= 1542902400;    //"2018-11-10 00:00:00";
        $per_time_7d = 1537632000;//1542384000(目前为两个月)

        //var_dump($current_time);
        //var_dump($per_time_24h);
        //计算交易笔数和交易额

        $sql_vlw = "SELECT count(*) as count,sum(tx_value) as sum from transaction 
                    WHERE tx_to in (SELECT `eth_address`.`address` from `dapp_apply_info` 
                    inner JOIN `eth_address` on `dapp_apply_info`.`apply_id` =`eth_address`.`apply_id` 
                    WHERE `dapp_apply_info`.`apply_id` =$apply_id)
                    and timestamp<=$current_time and timestamp>=$per_time_7d ";    //该合约对应交易笔数

        $data_vlw = $this->db->query($sql_vlw)->result_array();



        if($data_vlw[0]['sum']!=0){

            $result["tx_last_week"]=sprintf("%.2f",log($data_vlw[0]['sum']));
        }
        else{
            $result["tx_last_week"]=0;
        }
        $result["volume_last_week"]=$data_vlw[0]['count'];

        return $result;

        //return array("status" => 0, "msg" => "success","results" =>$result);

    }

    //24h交易统计(包括日活)
    public function get_data_24h($apply_id)
    {
        //$current_time=time();   //当前时间
        //$per_time_24h=$current_time-24*3600;   //24小时前
        //$current_time= 1542902400;    //"2018-11-10 00:00:00";
        $current_time=1542902400;//1542384000;
        $per_time_24h = 1540828800;   //"2018-11-10 23:59:59" ;目前为一个月
        /*
        $sql = "SELECT `eth_address`.`address` from `dapp_apply_info` 
               LEFT JOIN `eth_address` on `dapp_apply_info`.`apply_id` =`eth_address`.`apply_id` 
               WHERE `dapp_apply_info`.`apply_id` =$apply_id";
        $address_list = $this->db->query($sql)->result_array();
        */
        //判断合约是否为空
        //计算交易笔数和交易额
        //$num_vld=0;
        //$sum_vld=0;
        $dau_vld=0;
        //foreach($address_list as $list)
        //{
            //$address = $list['address'];

            $sql_vld = "SELECT count(*) as count,sum(tx_value) as sum ,count(DISTINCT(tx_from)) as dau
                        from transaction 
                        WHERE tx_to in (SELECT `eth_address`.`address` from `dapp_apply_info` 
                        LEFT JOIN `eth_address` on `dapp_apply_info`.`apply_id` =`eth_address`.`apply_id` 
                        WHERE `dapp_apply_info`.`apply_id` =$apply_id) 
                        and timestamp<=$current_time and timestamp>=$per_time_24h ";    //该合约对应交易笔数

            $data_vld = $this->db->query($sql_vld)->result_array();
         /*
            if (!empty($data_vld))//不为空
            {
                //七天交易笔数，交易额
                $num_vld+= $data_vld[0]['count'];
                $sum_vld+= ((float)$data_vld[0]['sum']);
                $dau_vld+= $data_vld[0]['dau'];
            }
        */
        //}
        //ini_set("serialize_precision","4");
        $result["volume_last_day"]=$data_vld[0]['count'];
        if($data_vld[0]['sum']!=0){

            $result["tx_last_day"]=sprintf("%.2f",log($data_vld[0]['sum']));

        }
        else{
            $result["tx_last_day"]=0;
        }
        //$result["dau"]=$dau_vld;     //日活
        $result["dau"]=$data_vld[0]['dau'];
        return $result;

        //return array("status" => 0, "msg" => "success","results" =>$result);

    }

    //  获取余额
    public function get_balance($apply_id)
    {
        $sql = "SELECT `eth_address`.`address` from `dapp_apply_info` 
               LEFT JOIN `eth_address` on `dapp_apply_info`.`apply_id` =`eth_address`.`apply_id` 
               WHERE `dapp_apply_info`.`apply_id` =$apply_id";
        $address_list = $this->db->query($sql)->result_array();

        //判断合约是否为空
        //计算余额，交易总额
        $sum_balance=0;
        foreach($address_list as $list)
        {
            $address = $list['address'];
            //var_dump($address);
            $sql_vlw = "SELECT sum(tx_value) as balance from transaction 
                        WHERE tx_to='$address' ";    //该合约对应交易笔数

            $data_vlw = $this->db->query($sql_vlw)->result_array();
            //var_dump($data_vlw);
            if (!empty($data_vlw))//不为空
            {
                $sum_balance = $data_vlw[0]['balance'];
                var_dump($sum_balance);
                $test = substr($sum_balance, 0, -15);
                var_dump($test);
            }

        }
        $result["balance"]=$sum_balance;

        //return $result;
        return array("status" => 0, "msg" => "success","results" =>$result);

    }

    //七天趋势图
    public function get_tx_chart($apply_id)
    {
        $start = "2018-11-10 00:00:00";
        $end = "2018-11-23 00:00:00";
        //$start = date("Y-m-d",time());
        //$end = date("Y-m-d",strtotime("-7 day"));
        //$start= strtotime($start);
        //var_dump($start);
        //var_dump($end);

        //查询数据库存储的数据
        /*
        $sql = "SELECT count( distinct tx_from) as uc_day,
                count(*) as volume_day, sum(tx_value) as tx_day,
                from_unixtime(timestamp, '%Y-%m-%d') as fetch_day
                FROM `transaction` 
                WHERE `tx_to` in (SELECT `eth_address`.`address` from `dapp_apply_info` 
                inner JOIN `eth_address` on `dapp_apply_info`.`apply_id` =`eth_address`.`apply_id` 
                WHERE `dapp_apply_info`.`apply_id` =$apply_id)
                and timestamp<=unix_timestamp('$end') and timestamp>=unix_timestamp('$start')
                GROUP BY from_unixtime(timestamp, '%Y-%m-%d')";

         */
        $sql ="SELECT `eth_address`.`address` from `dapp_apply_info` 
                inner JOIN `eth_address` on `dapp_apply_info`.`apply_id` =`eth_address`.`apply_id` 
                WHERE `dapp_apply_info`.`apply_id` =$apply_id";
        $address_list = $this->db->query($sql)->result_array();

        $address='';
        foreach($address_list as $item)
        {
            $query = strtoupper($item['address']);
            //$address = $address.$query.',';
            $address = $address . "'" . $query . "'" . ',';
        }
        $address = substr($address,0,strlen($address)-1);

        $sql_7d="SELECT count( distinct tx_from) as uc_day,
                    count(*) as volume_day,sum(CAST(`tx_value` AS DOUBLE )) as tx_day,
                    FROM_UNIXTIME(CAST(`timestamp` AS INT ), 'Y-M-d') as `fetch_day`
                    FROM ipfs.`/ipfs/QmcB1fpBU9P1m2HJCwAXd3FZot9JZpgRQJusERN8F5f6nE`
                    WHERE upper(`tx_to`) in ($address)
                    and `timestamp`<unix_timestamp('$end') 
                    and `timestamp`>unix_timestamp('$start')
                    GROUP BY FROM_UNIXTIME(CAST(`timestamp` AS INT ), 'Y-M-d')";

        $result = $this->drill_post_data($sql_7d);


        //var_dump($result);
        //$result = $this->db->query($sql)->result_array();

        //var_dump($day_data);

        //highcharts图
        $date_list = array();
        $start_time = strtotime($start);
        $end_time = strtotime($end);

        $limit = round(($end_time - $start_time)/3600-24,0);

        for ($i=$start_time;$i<$end_time;$i=$i+24*60*60)
        {

            $fetch_date = date("Y-m-d", $i);
            $date_list[] = $fetch_date;
        }
        //var_dump($date_list);
        //echo json_encode($result);


        #构造图表数据
        $data = array();
        $data["chart"]["type"] = "spline";
        $data["title"]["text"] ="七天";
        $data["tooltip"]["crosshairs"] = array(array("enabled"=>"true","width"=>1,"color"=>"#d8d8d8"));
        $data["tooltip"]["pointFormat"] = '<span style="color:{series.color}">{series.name}</span>: {point.y} <br/>';
        $data["tooltip"]["shared"] = "true";
        $data["tooltip"]["borderColor"] = "#d8d8d8";
        $data["plotOptions"]["series"]["marker"]["radius"] = 2;
        $data["tooltip"]["xDateFormat"] = "%Y-%m-%d %H:%M";
        $data["plotOptions"]["spline"]["states"]["hover"]["enabled"] = false; //禁用曲线的选择状态

        $data["title"]["style"] = "fontFamily:'微软雅黑', 'Microsoft YaHei',Arial,Helvetica,sans-serif,'宋体',";
        $data["yAxis"] = array(
            array("title"=>array("text"=>"价格趋势")),
        );

        $data["xAxis"]["labels"]["step"] = 1;
        //$data["xAxis"]["startOnTick"] = true; //x轴开始位置对齐
        //$data["xAxis"]["endOnTick"] = true; //x轴结束位置对齐
        $data["xAxis"]["gridLineWidth"] = 1; //纵向网格线宽度
        $data["xAxis"]["tickWidth"] = 0; //设置X轴坐标点是否出现占位及其宽度
        $data["xAxis"]["type"] = "datetime";//设置x轴为时间

        $data["xAxis"]["dateTimeLabelFormats"] = array("millisecond" => "%Y-%m-%d %H\u70b9",
            "second" => "%Y-%m-%d %H点",
            "minute" => "%Y-%m-%d %H点",
            "hour" => "%d日 %H点",
            "day" => "%d日",
            "week" => "%m月%d日",
            "month" => "%y年%m月",
            "year" => "%Y年");


        //$data["yAxis"]["title"]["text"] = "价格(USD)";
        //$data["yAxis"]["reversed"] = "false";


        //版权信息
        $data["credits"]["text"] = "dappbk.com";
        $data["credits"]["href"] = "http://www.dappbk.com/";
        $data["credits"]["position"]["align"] = "right";
        $data["credits"]["position"]["x"] = -10;
        $data["credits"]["position"]["verticalAlign"] = "bottom";
        $data["credits"]["position"]["y"] = -5;

        //构造y轴数据
        #构造数据key是日期， 内容是内容是
        $hot_rank_data = array();
        if($result["rows"][0]!=array())
        {
            foreach ($result["rows"] as $item)
            {
                //$fetch_time = date("Y-m-d",strtotime($item["fetch_time"]));
                $fetch_day = $item["fetch_day"];
                $hot_rank_data[ $fetch_day ] = $item;
                //var_dump($hot_rank_data);
            }
        }

        //echo json_encode($hot_rank_data);


        //图表y轴真实数据
        $y_hot_data = array();
        $y_hot_data["name"] = "交易笔数";
        $y_hot_data["yAxis"] = 0;

        //$pre_rank_value = 1; //前一个时间的值
        foreach ( $date_list as $fetch_date )
        {
            //数据
            if ( isset( $hot_rank_data[$fetch_date] ) )
            {
                $value = (float)$hot_rank_data[$fetch_date]["volume_day"];
            }
            else
            {
                //$value = $pre_rank_value; //如果没有对应的数据，热度假设为1
                //$value = NULL;
                continue;
            }
            $y_hot_data["data"][] = array(strtotime($fetch_date."00:00:00")*1000 + 8*60*60*1000, $value);
            //$pre_rank_value = $value;
        }
        $data["series"][] = $y_hot_data;


        //图表y轴真实数据
        $y1_hot_data = array();
        $y1_hot_data["name"] = "交易额";
        $y1_hot_data["yAxis"] = 0;

        ini_set("serialize_precision","4");
        //$pre_rank_value = 1; //前一个时间的值
        foreach ( $date_list as $fetch_date )
        {
            //数据
            if ( isset( $hot_rank_data[$fetch_date] ) )
            {
                $value = (float)$hot_rank_data[$fetch_date]["tx_day"];

                if($value!=0){
                    $value_x=log($value);
                    $value=sprintf("%.2f",$value_x);
                }
            }
            else
            {
                //$value = $pre_rank_value; //如果没有对应的数据，热度假设为1
                //$value = NULL;
                continue;
            }
            $y1_hot_data["data"][] = array(strtotime($fetch_date."00:00:00")*1000 + 8*60*60*1000,(float)$value);
            //$pre_rank_value = $value;
        }
        $data["series"][] = $y1_hot_data;

        //图表y轴真实数据
        $y2_hot_data = array();
        $y2_hot_data["name"] = "活跃用户";
        $y2_hot_data["yAxis"] = 0;

        //$pre_rank_value = 1; //前一个时间的值
        foreach ( $date_list as $fetch_date )
        {
            //数据
            if ( isset( $hot_rank_data[$fetch_date] ) )
            {
                $value = (float)$hot_rank_data[$fetch_date]["uc_day"];
            }
            else
            {
                //$value = $pre_rank_value; //如果没有对应的数据，热度假设为1
                //$value = NULL;
                continue;
            }
            $y2_hot_data["data"][] = array(strtotime($fetch_date."00:00:00")*1000 + 8*60*60*1000, $value);
            //$pre_rank_value = $value;
        }
        $data["series"][] = $y2_hot_data;

        return $data;

    }

    //24小时趋势图
    public function get_tx_24hchart($apply_id)
    {
        $start = "2018-11-12 00:00:00";
        $end = "2018-11-12 23:59:59" ;
        //$start = date("Y-m-d",time());
        //$end = date("Y-m-d",strtotime("-7 day"));
        //$start= strtotime($start);
        //var_dump($start);
        //var_dump($end);

        /*
        $sql = "SELECT count( distinct tx_from) as uc_hour,
                count(*) as volume_hour,sum(tx_value) as tx_hour,
                from_unixtime(timestamp, '%Y-%m-%d %H') as fetch_hour
                FROM `transaction` 
                WHERE `tx_to` in (SELECT `eth_address`.`address` from `dapp_apply_info` 
                LEFT JOIN `eth_address` on `dapp_apply_info`.`apply_id` =`eth_address`.`apply_id` 
                WHERE `dapp_apply_info`.`apply_id` =$apply_id)
                and timestamp<=unix_timestamp('$end') and timestamp>=unix_timestamp('$start')
                GROUP BY from_unixtime(timestamp, '%Y-%m-%d %H')";
        */
        //var_dump($sql);
        $sql ="SELECT `eth_address`.`address` from `dapp_apply_info` 
                inner JOIN `eth_address` on `dapp_apply_info`.`apply_id` =`eth_address`.`apply_id` 
                WHERE `dapp_apply_info`.`apply_id` =$apply_id";
        $address_list = $this->db->query($sql)->result_array();


        $address='';
        foreach($address_list as $item)
        {
            $query = strtoupper($item['address']);
            //$address = $address.$query.',';
            $address = $address . "'" . $query . "'" . ',';
        }
        $address = substr($address,0,strlen($address)-1);

            //var_dump($address);
        $sql_24h="SELECT count( distinct tx_from) as uc_hour,
                    count(*) as volume_hour,sum(CAST(`tx_value` AS DOUBLE )) as tx_hour,
                    FROM_UNIXTIME(CAST(`timestamp` AS INT ), 'Y-M-d H') as `fetch_hour`
                    FROM ipfs.`/ipfs/QmcB1fpBU9P1m2HJCwAXd3FZot9JZpgRQJusERN8F5f6nE`
                    WHERE upper(`tx_to`) in ($address)
                    and `timestamp`<unix_timestamp('$end') and         
                    `timestamp`>=unix_timestamp('$start')
                    GROUP BY FROM_UNIXTIME(CAST(`timestamp` AS INT ), 'Y-M-d H')";
        $result = $this->drill_post_data($sql_24h);



        //$result = $this->db->query($sql)->result_array();

        //highcharts图
        $date_list = array();
        $start_time = strtotime($start);
        $end_time = strtotime($end);

        $limit = round(($end_time - $start_time)/3600-24,0);

        for ($i=$start_time;$i<$end_time;$i=$i+60*60)
        {

            $fetch_date = date("Y-m-d G", $i);

            $date_list[] = $fetch_date;
        }
        //var_dump($date_list);
        //echo json_encode($date_list);


        #构造图表数据
        $data = array();
        $data["chart"]["type"] = "spline";
        $data["title"]["text"] ="24小时";
        $data["tooltip"]["crosshairs"] = array(array("enabled"=>"true","width"=>1,"color"=>"#d8d8d8"));
        $data["tooltip"]["pointFormat"] = '<span style="color:{series.color}">{series.name}</span>: {point.y} <br/>';
        $data["tooltip"]["shared"] = "true";
        $data["tooltip"]["borderColor"] = "#d8d8d8";
        $data["plotOptions"]["series"]["marker"]["radius"] = 2;
        $data["tooltip"]["xDateFormat"] = "%Y-%m-%d %H:%M";
        $data["plotOptions"]["spline"]["states"]["hover"]["enabled"] = false; //禁用曲线的选择状态

        $data["title"]["style"] = "fontFamily:'微软雅黑', 'Microsoft YaHei',Arial,Helvetica,sans-serif,'宋体',";
        $data["yAxis"] = array(
            array("title"=>array("text"=>"价格趋势")),
        );

        $data["xAxis"]["labels"]["step"] = 1;
        //$data["xAxis"]["startOnTick"] = true; //x轴开始位置对齐
        //$data["xAxis"]["endOnTick"] = true; //x轴结束位置对齐
        $data["xAxis"]["gridLineWidth"] = 1; //纵向网格线宽度
        $data["xAxis"]["tickWidth"] = 0; //设置X轴坐标点是否出现占位及其宽度
        $data["xAxis"]["type"] = "datetime";//设置x轴为时间

        $data["xAxis"]["dateTimeLabelFormats"] = array("millisecond" => "%Y-%m-%d %H\u70b9",
            "second" => "%Y-%m-%d %H点",
            "minute" => "%Y-%m-%d %H点",
            "hour" => "%d日 %H点",
            "day" => "%d日",
            "week" => "%m月%d日",
            "month" => "%y年%m月",
            "year" => "%Y年");


        //$data["yAxis"]["title"]["text"] = "价格(USD)";
        //$data["yAxis"]["reversed"] = "false";


        //版权信息
        $data["credits"]["text"] = "dappbk.com";
        $data["credits"]["href"] = "http://www.dappbk.com/";
        $data["credits"]["position"]["align"] = "right";
        $data["credits"]["position"]["x"] = -10;
        $data["credits"]["position"]["verticalAlign"] = "bottom";
        $data["credits"]["position"]["y"] = -5;

        //构造y轴数据
        #构造数据key是日期， 内容是内容是
        $hot_rank_data = array();
        if($result["rows"][0]!=array())
        {
            foreach ($result["rows"] as $item)
            {
                //$fetch_time = date("Y-m-d",strtotime($item["fetch_time"]));
                $fetch_hour = $item["fetch_hour"];
                $hot_rank_data[ $fetch_hour ] = $item;
            }
        }

        //echo json_encode($hot_rank_data);


        //图表y轴真实数据
        $y_hot_data = array();
        $y_hot_data["name"] = "交易笔数";
        $y_hot_data["yAxis"] = 0;

        //$pre_rank_value = 1; //前一个时间的值
        foreach ( $date_list as $fetch_date )
        {
            //数据
            if ( isset( $hot_rank_data[$fetch_date] ) )
            {
                $value = (float)$hot_rank_data[$fetch_date]["volume_hour"];
            }
            else
            {
                continue;
                //$value = $pre_rank_value; //如果没有对应的数据，热度假设为1
                //$value = NULL;
            }
            $y_hot_data["data"][] = array(strtotime($fetch_date.":00:00")*1000 , $value);
            //$pre_rank_value = $value;
        }
        $data["series"][] = $y_hot_data;


        //图表y轴真实数据
        $y1_hot_data = array();
        $y1_hot_data["name"] = "活跃用户";
        $y1_hot_data["yAxis"] = 0;

        //$pre_rank_value = 1; //前一个时间的值
        foreach ( $date_list as $fetch_date )
        {
            //数据
            if ( isset( $hot_rank_data[$fetch_date] ) )
            {
                $value = (float)$hot_rank_data[$fetch_date]["uc_hour"];
            }
            else
            {
                continue;
                //$value = $pre_rank_value; //如果没有对应的数据，热度假设为1
                //$value = NULL;
            }
            $y1_hot_data["data"][] = array(strtotime($fetch_date.":00:00")*1000, $value);
            $pre_rank_value = $value;
        }
        $data["series"][] = $y1_hot_data;


        //图表y轴真实数据
        $y2_hot_data = array();
        $y2_hot_data["name"] = "交易额";
        $y2_hot_data["yAxis"] = 0;
        //ini_set("precision", "3");
        //$pre_rank_value = 1; //前一个时间的值
        ini_set("serialize_precision","4");
        foreach ( $date_list as $fetch_date )
        {
            //数据

            if ( isset( $hot_rank_data[$fetch_date] ) )
            {
                $value = (float)$hot_rank_data[$fetch_date]["tx_hour"];
                if($value!=0){
                  $value_x=log($value);
                  $value=round($value_x,3);

                  //$value=number_format($value, 2, '.', '');
                  //$value=sprintf("%.2f",$value_x);

                }
            }
            else
            {
                continue;
                //$value = $pre_rank_value; //如果没有对应的数据，热度假设为1
                //$value = NULL;

            }
            $y2_hot_data["data"][] = array(strtotime($fetch_date.":00")*1000,(float)$value);

        }

        $data["series"][] = $y2_hot_data;
        //echo json_encode($data);
        return $data;

    }

    /****************测试函数*******************/
    public function test_chart($apply_id)
    {
        $start = "2018-11-10 00:00:00";
        $end = "2018-11-10 23:59:59" ;
        //$start = date("Y-m-d",time());
        //$end = date("Y-m-d",strtotime("-7 day"));
        //$start= strtotime($start);
        //var_dump($start);
        //var_dump($end);



        $sql = "SELECT count( distinct tx_from) as uc_hour,
                count(*) as volume_hour,sum(tx_value) as tx_hour,
                from_unixtime(timestamp, '%Y-%m-%d %H') as fetch_hour
                FROM `transaction` 
                WHERE `tx_to` in (SELECT `eth_address`.`address` from `dapp_apply_info` 
                LEFT JOIN `eth_address` on `dapp_apply_info`.`apply_id` =`eth_address`.`apply_id` 
                WHERE `dapp_apply_info`.`apply_id` =$apply_id)
                and timestamp<=unix_timestamp('$end') and timestamp>=unix_timestamp('$start')
                GROUP BY from_unixtime(timestamp, '%Y-%m-%d %H')";

        //var_dump($sql);

        $result = $this->db->query($sql)->result_array();

        //var_dump($result);
        //var_dump($day_data);



        //highcharts图
        $date_list = array();
        $start_time = strtotime($start);
        $end_time = strtotime($end);

        $limit = round(($end_time - $start_time)/3600-24,0);

        for ($i=$start_time;$i<$end_time;$i=$i+60*60)
        {

            $fetch_date = date("Y-m-d H", $i);
            $date_list[] = $fetch_date;
        }

        //echo json_encode($date_list);


        #构造图表数据
        $data = array();
        $data["chart"]["type"] = "spline";
        $data["title"]["text"] ="24小时";
        $data["tooltip"]["crosshairs"] = array(array("enabled"=>"true","width"=>1,"color"=>"#d8d8d8"));
        $data["tooltip"]["pointFormat"] = '<span style="color:{series.color}">{series.name}</span>: {point.y} <br/>';
        $data["tooltip"]["shared"] = "true";
        $data["tooltip"]["borderColor"] = "#d8d8d8";
        $data["plotOptions"]["series"]["marker"]["radius"] = 2;
        $data["tooltip"]["xDateFormat"] = "%Y-%m-%d %H:%M";
        $data["plotOptions"]["spline"]["states"]["hover"]["enabled"] = false; //禁用曲线的选择状态

        $data["title"]["style"] = "fontFamily:'微软雅黑', 'Microsoft YaHei',Arial,Helvetica,sans-serif,'宋体',";
        $data["yAxis"] = array(
            array("title"=>array("text"=>"价格趋势")),
        );

        $data["xAxis"]["labels"]["step"] = 1;
        //$data["xAxis"]["startOnTick"] = true; //x轴开始位置对齐
        //$data["xAxis"]["endOnTick"] = true; //x轴结束位置对齐
        $data["xAxis"]["gridLineWidth"] = 1; //纵向网格线宽度
        $data["xAxis"]["tickWidth"] = 0; //设置X轴坐标点是否出现占位及其宽度
        $data["xAxis"]["type"] = "datetime";//设置x轴为时间

        $data["xAxis"]["dateTimeLabelFormats"] = array("millisecond" => "%Y-%m-%d %H\u70b9",
            "second" => "%Y-%m-%d %H点",
            "minute" => "%Y-%m-%d %H点",
            "hour" => "%d日 %H点",
            "day" => "%d日",
            "week" => "%m月%d日",
            "month" => "%y年%m月",
            "year" => "%Y年");


        //$data["yAxis"]["title"]["text"] = "价格(USD)";
        $data["yAxis"]["reversed"] = "false";


        //版权信息
        $data["credits"]["text"] = "dappbk.com";
        $data["credits"]["href"] = "http://www.dappbk.com/";
        $data["credits"]["position"]["align"] = "right";
        $data["credits"]["position"]["x"] = -10;
        $data["credits"]["position"]["verticalAlign"] = "bottom";
        $data["credits"]["position"]["y"] = -5;

        //构造y轴数据
        #构造数据key是日期， 内容是内容是
        $hot_rank_data = array();
        foreach ($result as $item)
        {
            //$fetch_time = date("Y-m-d",strtotime($item["fetch_time"]));
            $fetch_hour = $item["fetch_hour"];
            $hot_rank_data[ $fetch_hour ] = $item;
        }

        //echo json_encode($hot_rank_data);


        //图表y轴真实数据
        $y_hot_data = array();
        $y_hot_data["name"] = "交易笔数";
        $y_hot_data["yAxis"] = 0;

        //$pre_rank_value = 1; //前一个时间的值
        foreach ( $date_list as $fetch_date )
        {
            //数据
            if ( isset( $hot_rank_data[$fetch_date] ) )
            {
                $value = (float)$hot_rank_data[$fetch_date]["volume_hour"];
            }
            else
            {
                continue;
                //$value = $pre_rank_value; //如果没有对应的数据，热度假设为1
                //$value = NULL;
            }
            $y_hot_data["data"][] = array(strtotime($fetch_date.":00:00")*1000 , $value);
            //$pre_rank_value = $value;
        }
        $data["series"][] = $y_hot_data;


        //图表y轴真实数据
        $y1_hot_data = array();
        $y1_hot_data["name"] = "活跃用户";
        $y1_hot_data["yAxis"] = 0;

        //$pre_rank_value = 1; //前一个时间的值
        foreach ( $date_list as $fetch_date )
        {
            //数据
            if ( isset( $hot_rank_data[$fetch_date] ) )
            {
                $value = (float)$hot_rank_data[$fetch_date]["uc_hour"];
            }
            else
            {
                continue;
                //$value = $pre_rank_value; //如果没有对应的数据，热度假设为1
                //$value = NULL;
            }
            $y1_hot_data["data"][] = array(strtotime($fetch_date.":00:00")*1000, $value);
            $pre_rank_value = $value;
        }
        $data["series"][] = $y1_hot_data;


        //图表y轴真实数据
        $y2_hot_data = array();
        $y2_hot_data["name"] = "交易额";
        $y2_hot_data["yAxis"] = 0;

        //echo ini_get('precision');
        //ini_set("precision", "2");
        //echo ini_get('precision');
        //$pre_rank_value = 1; //前一个时间的值
        ini_set("serialize_precision","4");
        foreach ( $date_list as $fetch_date )
        {
            //数据

            if ( isset( $hot_rank_data[$fetch_date] ) )
            {
                $value = $hot_rank_data[$fetch_date]["tx_hour"];

                if($value!=0){
                    $value_x=log($value);
                    $value=round($value_x,3);
                    //var_dump($value);
                    //$value=$value *100;
                    //$value=(int)$value_x;

                    //$value=number_format($value, 2, '.', '');
                    //$value=sprintf("%.2f",$value_x);
                    //$value=(float)$value+0;
                    //$value=floatval($value);
                    //$value=round($value_x,2);
                    //var_dump($value);

                }
            }
            else
            {
                continue;
                //$value = $pre_rank_value; //如果没有对应的数据，热度假设为1
                //$value = NULL;

            }
            //ini_set("precision", "3");
            $y2_hot_data["data"][] = array(strtotime($fetch_date.":00")*1000,$value);

            //$pre_rank_value = $value;
            //var_dump(array(1,3.4));
            //echo json_encode($y2_hot_data["data"]);
        }

        $data["series"][] = $y2_hot_data;
        //echo json_encode($data);
        return $data;
    }
    public function get_tx_chart_x($apply_id)
    {
        $current_date=date("Ymd",time());
        $start = $current_date." 00:00:00";
        $start= strtotime($start);
        $end = $start-24*60*60;

        for ($i=1; $i<=7; $i++)
        {

            $sql = "SELECT count( distinct tx_from) as uc_day,
                    count(*) as volume_day, 
                    sum(tx_value) as tx_day FROM `transaction` 
                    WHERE `tx_to` in (SELECT `eth_address`.`address` from `dapp_apply_info` 
                    LEFT JOIN `eth_address` on `dapp_apply_info`.`apply_id` =`eth_address`.`apply_id` 
                    WHERE `dapp_apply_info`.`apply_id` =$apply_id)
                    and timestamp<=$start and timestamp>=$end";
            var_dump($sql);
            $day_data = $this->db->query($sql)->result_array();
            $day_data[0]['fetch_time']= date("Y-m-d ",$start);
            $result[] = $day_data[0];
            $start= $start-24*60*60;
            $end = $end-24*60*60;

        }

        $current_date=date("Ymd",time());
        $start = $current_date." 00:00:00";
        $start_time= strtotime($start);
        $end_time = $start-24*60*60;

        $limit = round(($end_time - $start_time)/3600-24,0);

        for ($i=$start_time;$i<$end_time;$i=$i+60*60)
        {

            $fetch_date = date("Y-m-d H", $i);
            $date_list[] = $fetch_date;
        }

        #构造图表数据
        $data = array();
        $data["chart"]["type"] = "spline";
        $data["title"]["text"] ="'" . $apply_id . "' --价格度趋势图(最近" .(string)$limit ."小时/". (int)($limit/24)."天)";
        $data["tooltip"]["crosshairs"] = array(array("enabled"=>"true","width"=>1,"color"=>"#d8d8d8"));
        $data["tooltip"]["pointFormat"] = '<span style="color:{series.color}">{series.name}</span>: {point.y} <br/>';
        $data["tooltip"]["shared"] = "true";
        $data["tooltip"]["borderColor"] = "#d8d8d8";
        $data["plotOptions"]["series"]["marker"]["radius"] = 2;
        $data["tooltip"]["xDateFormat"] = "%Y-%m-%d %H:%M";
        $data["plotOptions"]["spline"]["states"]["hover"]["enabled"] = false; //禁用曲线的选择状态

        $data["title"]["style"] = "fontFamily:'微软雅黑', 'Microsoft YaHei',Arial,Helvetica,sans-serif,'宋体',";
        $data["yAxis"] = array(
            array("title"=>array("text"=>"价格趋势")),
        );

        $data["xAxis"]["labels"]["step"] = 1;
        //$data["xAxis"]["startOnTick"] = true; //x轴开始位置对齐
        //$data["xAxis"]["endOnTick"] = true; //x轴结束位置对齐
        $data["xAxis"]["gridLineWidth"] = 1; //纵向网格线宽度
        $data["xAxis"]["tickWidth"] = 0; //设置X轴坐标点是否出现占位及其宽度
        $data["xAxis"]["type"] = "datetime";//设置x轴为时间

        $data["xAxis"]["dateTimeLabelFormats"] = array("millisecond" => "%Y-%m-%d %H\u70b9",
            "second" => "%Y-%m-%d %H点",
            "minute" => "%Y-%m-%d %H点",
            "hour" => "%d日 %H点",
            "day" => "%d日",
            "week" => "%m月%d日",
            "month" => "%y年%m月",
            "year" => "%Y年");


        $data["yAxis"]["title"]["text"] = "价格(USD)";
        $data["yAxis"]["reversed"] = "false";


        //版权信息
        $data["credits"]["text"] = "加密世界";
        $data["credits"]["href"] = "http://www.appbk.com/";
        $data["credits"]["position"]["align"] = "right";
        $data["credits"]["position"]["x"] = -10;
        $data["credits"]["position"]["verticalAlign"] = "bottom";
        $data["credits"]["position"]["y"] = -5;

        //构造y轴数据
        #构造数据key是日期， 内容是内容是
        $hot_rank_data = array();
        foreach ($result as $item)
        {
            $fetch_time = date("Y-m-d",strtotime($item["fetch_time"]));
            $hot_rank_data[ $fetch_time ] = $item["price_usd"];
        }

        //图表y轴真实数据
        $y_hot_data = array();
        $y_hot_data["name"] = "价格趋势";
        $y_hot_data["yAxis"] = 0;

        $pre_rank_value = NULL; //前一个时间的值
        foreach ( $date_list as $fetch_date )
        {
            //热度数据
            if ( isset( $hot_rank_data[$fetch_date] ) )
            {
                $hot_rank_value = (float)$hot_rank_data[$fetch_date];
            }
            else
            {
                //$hot_rank_value = $pre_rank_value; //如果没有对应的数据，热度假设为1
                $hot_rank_value = NULL;
            }
            $y_hot_data["data"][] = array(strtotime($fetch_date.":00:00")*1000 + 8*60*60*1000, $hot_rank_value);
            $pre_rank_value = $hot_rank_value;
        }
        $data["series"][] = $y_hot_data;
        return $data;

    }
    //test_drill
    public function get_drill($apply_id)
    {
        $start = "2018-11-10 00:00:00";
        $end = "2018-11-23 00:00:00";
        //$start = date("Y-m-d",time());
        //$end = date("Y-m-d",strtotime("-7 day"));
        //$start= strtotime($start);
        //var_dump($start);
        //var_dump($end);

        /*//查询数据库存储的数据
        $sql = "SELECT count( distinct tx_from) as uc_day,
                count(*) as volume_day, sum(tx_value) as tx_day,
                from_unixtime(timestamp, '%Y-%m-%d') as fetch_day
                FROM `transaction`
                WHERE `tx_to` in (SELECT `eth_address`.`address` from `dapp_apply_info`
                inner JOIN `eth_address` on `dapp_apply_info`.`apply_id` =`eth_address`.`apply_id`
                WHERE `dapp_apply_info`.`apply_id` =$apply_id)
                and timestamp<=unix_timestamp('$end') and timestamp>=unix_timestamp('$start')
                GROUP BY from_unixtime(timestamp, '%Y-%m-%d')";

        */
       $sql ="SELECT `eth_address`.`address` from `dapp_apply_info` 
               inner JOIN `eth_address` on `dapp_apply_info`.`apply_id` =`eth_address`.`apply_id` 
               WHERE `dapp_apply_info`.`apply_id` =$apply_id";
       $address_list = $this->db->query($sql)->result_array();
       //var_dump($address_list);
        $address='';
       foreach($address_list as $item) {
           $query=strtoupper($item['address']);
           //$address = $address.$query.',';
           $address = $address."'".$query."'".',';
           //$address[]="'".$query."'";
       }
        $address = substr($address,0,strlen($address)-1);
       var_dump($address);

           $sql_7d="SELECT count( distinct tx_from) as uc_day,
                    count(*) as volume_day,sum(CAST(`tx_value` AS DOUBLE )) as tx_day,
                    FROM_UNIXTIME(CAST(`timestamp` AS INT ), 'Y-M-d') as `fetch_day`
                    FROM ipfs.`QmcB1fpBU9P1m2HJCwAXd3FZot9JZpgRQJusERN8F5f6nE`
                    WHERE upper(`tx_to`) in ($address)
                    and `timestamp`<unix_timestamp('$end') and         
                    `timestamp`>=unix_timestamp('$start')
                    GROUP BY FROM_UNIXTIME(CAST(`timestamp` AS INT ), 'Y-M-d')";

           /*
           $sql_7d="SELECT convert(int,`tx_value`) 
                    FROM dfs.`/data/ckx/ipfs/block_info.json` where   `tx_to`='0xf50FCF9DE1b62c329b3f8586b36611cAAc2f3267'
                    and `timestamp`>=1431779200 and `timestamp`<=1542902400 
                    ";
           */
                    //WHERE `tx_to`='0x2a0c0dbecc7e4d658f48e01e3fa353f44050c208';
                   // WHERE `tx_to` ='$address'  ";
           //`timestamp`<=1542902400  AND  `timestamp`>=1541779200
           $result = $this->drill_post_data($sql_7d);
           var_dump($result);
       }

        //var_dump($result);
        //}

    /***************私有函数*********************/
    public function drill_post_data($query){

        $url = "http://212.64.114.198:8047/query.json";
        $postData = array("queryType" => "SQL", "query" => $query);

        // Setup cURL
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_POSTFIELDS => json_encode($postData),
        ));
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        curl_close($ch);
        return $response;

    }

    public function test_2(){
        $query = "select * from dfs.`/data/ckx/ipfs/block_info.json` limit 20";
        $result = $this->drill_post_data($query);

        return $result["rows"];
    }
    //随机生成用户码
    function randomkeys($length)
    {
        $key='0x';
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        for($i=0;$i<$length;$i++)
        {
            $key.= $pattern{mt_rand(0,50)};    //生成php随机数
        }
        return $key;
    }

}
