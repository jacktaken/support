<?php
namespace Oosuite\Support;

use Illuminate\Support\Facades\DB;

class Model extends \Illuminate\Database\Eloquent\Model
{
    protected static $obj;
    public static $field = [];

    /**
     * 通用筛选条件
     * join查询的话，字段直接加前缀，示例：<input type="text" name="u.name">
     * $column 使用join查询的情况把其他表字段传进来
     */
    public static function fiterWhere($where=[],$whereStr='',$placeholder=[],$column=[]) {
        $columns = self::getColumns();
        if ($column) $columns=array_merge($column,$columns);
        $search = request()->all();
        unset($search['page'],$search['limit'],$search['sort'],$search['by'],$search['ids']);
        foreach($search as $searchName=>$searchVal) {
            if ($searchName=='keyword' || $searchVal==='' || $searchVal===null) continue;
            $prefix = '';//whereRaw如果是join查询的话需要表前缀
            $num    = strpos($searchName,'.');
            if ($num !== false) $prefix=env('DB_PREFIX');
            if ($searchName=='search_field') {//搜索框搜索
                if (isset($search['keyword']) && $search['keyword']) {
                    $keywordArr = explode("|",$searchVal);
                    if (count($keywordArr)>1){
                        $closeStr='';
                        foreach ($keywordArr as $val){
                            $closeStr.=$closeStr?" or {$prefix}{$val} like ? ":" ({$prefix}{$val} like ? ";
                            $placeholder[] = "%{$search['keyword']}%";
                        }
                        $closeStr.=') ';
                        $whereStr.=$whereStr?" and {$closeStr} ":$closeStr;
                    }else{
                        $where[]=[$searchVal,'like',"%".$search['keyword']."%"];
                    }
                }
            } else {//其他搜索项
                $field = $searchName;
                if ($num===false) $num=strpos($field,'-');
                if ($num!==false) $field=substr($field,$num+1);
                if (!in_array($field,$columns)) continue;//验证是否mysql表字段
                $controlArr = explode("-",$searchName);
                if (in_array($controlArr[0],['ex'])) continue;//排除前缀ex的参数
                if($controlArr[0] == "range") {//时间区间搜索，返回时间戳，时间符号( - )根据插件的文本修改
                    $valArr = explode(' - ',$searchVal);
                    if (isset($valArr[1])){
                        $where[] = [$controlArr[1],'>=',strtotime($valArr[0].' 00:00:00')];
                        $where[] = [$controlArr[1],'<=',strtotime($valArr[1].' 23:59:59')];
                    }else{
                        $where[] = [$controlArr[1],'>=',strtotime($valArr[0].' 00:00:00')];
                        $where[] = [$controlArr[1],'<=',strtotime($valArr[0].' 23:59:59')];
                    }
                }elseif ($controlArr[0] == "date"){//时间区间搜索，返回时间格式，时间符号( - )根据插件的文本修改
                    $valArr = explode(' - ',$searchVal);
                    if (isset($valArr[1])){
                        $where[] = [$controlArr[1],'>=',$valArr[0].' 00:00:00'];
                        $where[] = [$controlArr[1],'<=',$valArr[1].' 23:59:59'];
                    }else{
                        $where[] = [$controlArr[1],'>=',$valArr[0].' 00:00:00'];
                        $where[] = [$controlArr[1],'<=',$valArr[0].' 23:59:59'];
                    }
                } else {//$searchVal = -1  一般定义下拉框全部的默认值为-1，所以排除条件
                    if ($searchVal<>-1) $where[]=[$controlArr[0],'=', $searchVal];
                }
            }
        }
        $whereRaw = $whereStr?[$whereStr,$placeholder]:[];
        return [$where,$whereRaw];
    }

    /*通用获取单条数据*/
    public static function infoData($where=[],$whereRaw=[],$orderBy=[],$groupByRaw='',$havingRaw=[],$join=[]){
        return self::buildWhere($where,$whereRaw)->buildJoin($join)
            ->buildSql(self::$field,$orderBy,$groupByRaw,$havingRaw)
            ->getInfo();
    }

    /*通用获取列表数据*/
    public static function listData($where=[],$whereRaw=[],$orderBy=[],$groupByRaw='',$havingRaw=[],$limit=0,$join=[]){
        return self::buildWhere($where,$whereRaw)->buildJoin($join)
            ->buildSql(self::$field,$orderBy,$groupByRaw,$havingRaw)
            ->getList($limit);
    }
    public static function listPage($where=[],$whereRaw=[],$orderBy=[],$groupByRaw='',$havingRaw=[],$join=[]){
        return self::buildWhere($where,$whereRaw)->buildJoin($join)
            ->buildSql(self::$field,$orderBy,$groupByRaw,$havingRaw)
            ->getPage();
    }

    /*通用获取分页数据*/
    public static function pageData($where=[],$whereRaw=[],$orderBy=[],$groupByRaw='',$havingRaw=[],$join=[]){
        $model = self::buildWhere($where,$whereRaw)->buildJoin($join);
        $total = self::total(self::$obj,$groupByRaw,$havingRaw);//total()必须在buildSql()之前
        $data  = $model->buildSql(self::$field,$orderBy,$groupByRaw,$havingRaw)->getPage();
        return self::definePage($data,$total);
    }

    /*支持insert,insertAll*/
    public static function addData($data){
        self::checkField($data);
        return self::query()->insert($data);
    }
    public static function addGetId($data){
        self::checkField($data);
        return self::query()->insertGetId($data);
    }
    //$update = ['aa'=>DB::raw('aa+1')];可实现自增自减
    public static function edit($where,$data,$whereRaw=[]){
        self::checkField($data);
        self::buildWhere($where,$whereRaw);
        return self::$obj->update($data);
    }
    public static function trash($where,$whereRaw=[]){
        self::buildWhere($where,$whereRaw);
        return self::$obj->delete();
    }
    public static function getCount($where=[],$whereRaw=[]){
        self::buildWhere($where,$whereRaw);
        return self::$obj->count();
    }
    public static function getValue($where,$field,$whereRaw=[]){
        self::buildWhere($where,$whereRaw);
        return self::$obj->value($field);
    }
    public static function getSum($where,$field,$whereRaw=[]){
        self::buildWhere($where,$whereRaw);
        return self::$obj->sum($field);
    }
    public static function getMax($where,$field,$whereRaw=[]){
        self::buildWhere($where,$whereRaw);
        return self::$obj->max($field);
    }
    public static function getMin($where,$field,$whereRaw=[]){
        self::buildWhere($where,$whereRaw);
        return self::$obj->min($field);
    }
    public static function getAvg($where,$field,$whereRaw=[]){
        self::buildWhere($where,$whereRaw);
        return self::$obj->avg($field);
    }

    /**
     * 初始化条件拼接--- in,or,between,闭包等查询，可使用 whereRaw 占位符
     * @param array $where = [['id','=',1],['role_id','>',1]] 或者 ['id'=>1]
     * @param array $whereRaw = ['id IN (1,2) AND age > ? OR name LIKE ?',[18,'%xue%']]
     * 示例：$whereRaw[0]='id=?'; $whereRaw[1][] = $id;
     */
    public static function buildWhere($where=[],$whereRaw=[]){
        self::$obj = self::query()->where($where);
        if ($whereRaw && is_array($whereRaw)){
            if (isset($whereRaw[2]) && $whereRaw[2]=='or'){
                self::$obj = self::$obj->orWhereRaw($whereRaw[0],$whereRaw[1]);
            }else{
                if($whereRaw[0])self::$obj = self::$obj->whereRaw($whereRaw[0],$whereRaw[1]??[]);
            }
        }
        //dump(self::$obj->toSql());//调试
        return new static;
    }

    /**
     * Join语句
     * $join = [['role','role.role_id','=','admin.role_id','right']];
     */
    public static function buildJoin($join=[]){
        foreach ($join as $val){
            if (isset($val[4]) && $val[4] == 'right') {
                self::$obj = self::$obj->rightJoin($val[0],$val[1],$val[2],$val[3]);
            } else {
                self::$obj = self::$obj->leftJoin($val[0],$val[1],$val[2],$val[3]);
            }
        }
        //dump(self::$obj->toSql());//调试
        return new static;
    }

    /**
     * 查询分页数据的总数
     * 必须在buildWhere()，buildJoin()之后，buildSql()之前调用
     * $model      = self::$obj
     * $groupByRaw = 'city,state'
     * $havingRaw  = [['id=? and sum(price) > ?'],[1,2500]]
     */
    public static function total($model,$groupByRaw='',$havingRaw=[]){
        if ($groupByRaw){
            if ($havingRaw && is_array($havingRaw)){
                if (isset($havingRaw[2]) && $havingRaw[2]=='or'){
                    $model = $model->orHavingRaw($havingRaw[0],$havingRaw[1]);
                }else{
                    if($havingRaw[0])$model=$model->havingRaw($havingRaw[0],$havingRaw[1]??[]);
                }
            }
            $total = $model->count(DB::raw('distinct '.$groupByRaw.''));
        }else{
            $total = $model->count();
        }
        return $total;
    }

    /**
     * sql语法拼接
     * $orderBy = [['id'],['time','desc']]
     * $groupByRaw = 'city,state'
     * $havingRaw  = [['id=? and sum(price) > ?'],[1,2500]]
     * sql_mode=only_full_group_by 不兼容解决
     * 1、配置文件databse.php 文件内strict 设为 false
     * 2、sql_mode=STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION
     */
    public static function buildSql($field=[],$orderBy=[],$groupByRaw='',$havingRaw=[]){
        if ($field) self::$obj=self::$obj->select($field);
        foreach ($orderBy as $val){
            self::$obj = self::$obj->orderBy($val[0],$val[1]??'asc');
        }
        if ($groupByRaw) self::$obj=self::$obj->groupByRaw($groupByRaw);
        if ($havingRaw && is_array($havingRaw)){
            if (isset($havingRaw[2]) && $havingRaw[2]=='or'){
                self::$obj = self::$obj->orHavingRaw($havingRaw[0],$havingRaw[1]);
            }else{
                if($havingRaw[0])self::$obj=self::$obj->havingRaw($havingRaw[0],$havingRaw[1]??[]);
            }
        }
        //dump(self::$obj->toSql());//调试
        return new static;
    }

    /**
     * 获取单条数据
     */
    public static function getInfo(){
        $info = self::$obj->first();
        return $info?$info->toArray():[];
    }

    /**
     * 获取列表数据
     */
    public static function getList($limit=0){
        if ($limit>0)self::$obj=self::$obj->limit($limit);
        return self::$obj->get()->toArray();
    }

    /**
     * 获取分页数据(分页数据总数方法total())
     */
    public static function getPage(){
        $limit = request('limit',env('DEFAULT_LIMIT',20));
        $limit = $limit>1000?1000:$limit;
        $page  = request('page',1);
        return self::$obj->forPage($page,$limit)->get()->toArray();
    }

    /**
     * 自定义分页所需要的数据
     * @param $data  当前页数据
     * @param $total 共几条数据
     * @param $show  最多显示按钮页数
     */
    public static function definePage($data,$total,$show=20){
        $limit = request('limit',env('DEFAULT_LIMIT',20));
        $limit = $limit>1000?1000:$limit;
        $page  = request('page',1);
        $param = request()->all();
        $param['page'] = $param['limit'] = 0;
        $param = '?'.http_build_query($param);//请求的所有参数
        $totalPage = intval(ceil($total / $limit));//总页数
        return compact('data','page','limit','total','totalPage','param','show');
    }

    /**
     * with通用语法
     * @param array $withModel = 示例：['images'=>['test'=>['test2'],'test1'],'options'=>['test3'],'variants']  注意必须：key=>[]
     * @param array $withField 字段定义
     * @param array $extendWhere 扩展条件
     * @return mixed
     */
    public static function withs($model,$withModel=[],$withField=[],$extendWhere=[]){
        $arr = [];
        foreach ($withModel as $key => $val){
            $with = is_array($val)?$key:$val;
            $arr[$with]=function($query)use($with,$val,$withField,$extendWhere){
                $field = [];
                if (isset($withField[$with])){
                    $field = $withField[$with];
                }
                if (isset($extendWhere[$with])){
                    if ($extendWhere[$with]){
                        $query = $query->where($extendWhere[$with]);
                    }
                }
                $query = $query->select($field);
                if (is_array($val)){
                    $this->withs($query,$val,$withField,$extendWhere);
                }
            };
        }
        if ($arr)$model=$model->with($arr);
        return $model;
    }

    /**
     * 获取当前Model的数据表字段
     * @return array 字段数组
     */
    public static function getColumns(){
        return (new static)->getConnection()->getSchemaBuilder()
            ->getColumnListing((new static)->getTable());
    }

    /**
     * 插入和更新数据时检查数据库是否存在该字段
     */
    public static function checkField($data){
        $columns = self::getColumns();
        if (is_array($data) && is_array(current($data))){
            $field = array_keys($data[0]);
        }else{
            $field = array_keys($data);
        }
        $diff = array_diff($field,$columns);
        if ($diff){
            throw new \Exception('mysql field not exists:'.implode(',',$diff));
        }
    }

}
