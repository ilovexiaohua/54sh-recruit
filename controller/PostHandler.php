<?php
namespace xinda\recruit\controller;

defined('PATH') OR exit('invalid path');

require_once dirname(__FILE__)."/../model/DB.php";
require_once dirname(__FILE__)."/../model/Log.php";
require_once dirname(__FILE__)."/../config/config.php";
require_once dirname(__FILE__)."/Handler.php";

use xinda\recruit\model\DB;
use xinda\recruit\model\Log;
use xinda\recruit\controller\Handler;

class PostHandler extends Handler{

    // protected $resource;

    // protected $action;

    protected $openid;

    protected $dbArray;

    protected $filterPost = array();

    protected $log;


    // protected $pdo;

    // protected $response = array(
    //     'stauts'   => 1,
    //     'response' => '操作成功',
    // );

    public function construct($action, $resource)
    {
        parent::construct($action,$resource);

        $this->log      = new Log('PostChannel');
        // $this->action   = $action;
        // $this->resource = $resource;
        
        // $this->pdo      = DB::getInstance(DSN, DB_USER, DB_PWD);
    }

    /**
     * response ajax
     * @return json
     */

    public function receive()
    { 
        $this->filterPost = $this->filter();
        if (is_array($this->filterPost)) {
            foreach ($this->filterPost as $key => $value) {
                //null or false
                if (false === $value or null == trim($value) ) {
                    exit($this->ajaxResponse(0,$key));//某一项false或者null
                }

            }
        } else {
            exit($this->ajaxResponse(0,$this->resource)); //请求资源不存在
        }
        // $this->filterPost['openid'] = $_SESSION['openid'];
        $this->filterPost['time']   = time();
        /**
         * 插入数据库
         */
        if ($this->action == 'insert') {
            try {
                $insertStatement = $this->pdo->insert(array_keys($this->filterPost))
                    ->into($this->resource)
                    ->values(array_values($this->filterPost));
                $insertId = $insertStatement->execute();
            } catch (\PDOException $e) {

                exit($this->ajaxResponse(0,$e->getMessage()));
                
            }

            exit($this->ajaxResponse());

        } elseif($this->action == 'update') {
            try {
                $updateStatement = $this->pdo->update($this->filterPost)
                    ->table($this->resource)
                    // ->where('openid', '=', $this->filterPost['openid']);
                    ->where('name','=','大');
                $affectedRows = $updateStatement->execute();
            }catch(\PDOException $e) {
                exit($this->ajaxResponse(0,$e->getMessage()));
            }

            exit($this->ajaxResponse());

        }else{
            exit($this->ajaxResponse(0,'invalid action'));

        }

    }


    private function ajaxResponse($status=1,$message=null,$type=1)
    {
        $this->response['status']=$status;
        $this->response['message']=$message ?? '操作成功';
        if(1===$type){
            return json_encode($this->response);
        }else{
            return $this->response;
        }
    }

    /**
     * 过滤检验post数据
     * @return array 过滤后的数据
     */
    public function filter()
    {
        $args = $this->getFilter($this->resource);
        if(!is_array($args)){
            return null;
        }else{
            return filter_input_array(INPUT_POST, $args);
        }

    }

    /**
     * 选择相应的过滤器
     * @param  string $args 过滤器名称
     * @return array       过滤器
     */
    public function getFilter($resource)
    {
        $options = array(
            'userInfo'   => array(
                'name'       => array(
                    'filter'  => FILTER_VALIDATE_REGEXP, //檢驗器
                    'options' => array('regexp' => '/^[\x80-\xff]+(·[\x80-\xff]+)*$/'), //匹配中文（含少数民族）
                ),
                'college'    => array(
                    'filter'  => FILTER_VALIDATE_REGEXP, //檢驗器
                    'options' => array('regexp' => '/^[\x80-\xff]+/'), //匹配中文（含少数民族）
                ),
                'class'      => FILTER_SANITIZE_SPECIAL_CHARS,
                'born'       => FILTER_SANITIZE_NUMBER_INT,
                'sex'        => FILTER_SANITIZE_NUMBER_INT,
                'student_id' => array(
                    'filter'  => FILTER_VALIDATE_REGEXP,
                    'options' => array('regexp' => '/\d{7,12}/'),
                ),
                'phone'      => array(
                    'filter'  => FILTER_VALIDATE_REGEXP,
                    'options' => array('regexp' => '/^((13[0-9])|(15[^4])|(18[0-9])|(17[0-8])|(147,145))\d{8}$/'),
                ),
            ),

            'resume' => array(
                'organization' => array(
                    'filter'  => FILTER_VALIDATE_REGEXP, //檢驗器
                    'options' => array('regexp' => '/^[\x80-\xff]+/'), //匹配中文
                ), //Sanitizing过滤器
                'department'   => array(
                    'filter'  => FILTER_VALIDATE_REGEXP, //檢驗器
                    'options' => array('regexp' => '/^[\x80-\xff]+/'), //匹配中文
                ),
                'introduce'    => FILTER_SANITIZE_SPECIAL_CHARS,
            ),

        );

        foreach ($options as $key => $value) {
            if ($resource == $key) {
                return $value;
            }

        }
        return null;
    }

}
