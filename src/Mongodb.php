<?php


namespace Oosuite\Support;

use Jenssegers\Mongodb\Eloquent\Model;

class Mongodb extends Model
{
    protected $connection = 'mongodb';

    protected static $obj;
    public static $field = [];

    public static function infoData($where=[],$orderBy=[],$groupBy=[]){
        return self::buildWhere($where)
            ->buildSql(self::$field,$orderBy,$groupBy)
            ->getInfo();
    }
    public static function listData($where=[],$orderBy=[],$groupBy=[],$limit=0){
        return self::buildWhere($where)
            ->buildSql(self::$field,$orderBy,$groupBy)
            ->getList($limit);
    }
    public static function listPage($where=[],$orderBy=[],$groupBy=[]){
        return self::buildWhere($where)
            ->buildSql(self::$field,$orderBy,$groupBy)
            ->getPage();
    }
    //目前暂无分组分页，后续调整
    public static function pageData($where=[],$orderBy=[]){
        $model = self::buildWhere($where);
        $total = self::$obj->count();
        $data  = $model->buildSql(self::$field,$orderBy)->getPage();
        return [$data,$total];
    }
    public static function edit($where,$data){
        self::buildWhere($where);
        return self::$obj->update($data);
    }
    public static function trash($where){
        self::buildWhere($where);
        return self::$obj->delete();
    }
    public static function getCount($where=[]){
        self::buildWhere($where);
        return self::$obj->count();
    }
    public static function getValue($where,$field){
        self::buildWhere($where);
        return self::$obj->value($field);
    }
    public static function getSum($where,$field){
        self::buildWhere($where);
        return self::$obj->sum($field);
    }
    public static function getMax($where,$field){
        self::buildWhere($where);
        return self::$obj->max($field);
    }
    public static function getMin($where,$field){
        self::buildWhere($where);
        return self::$obj->min($field);
    }
    public static function getAvg($where,$field){
        self::buildWhere($where);
        return self::$obj->avg($field);
    }

    /**
     * 初始化条件拼接
     * @param array $where =  ['id',1] 或 ['id','in',[1,2]] 或 ['id','between',[1,3]]
     * 增加闭包的条件：['or',[['id','=',2]],'closure']
     * 所有条件：[['id',1],['id','>',1],['id','=',2,'or'],['id','in',[1,2]],['id','between',[1,3]],['or',[['id','=',2]],'closure']]
     */
    public static function buildWhere($where=[]){
        self::$obj = new static;
        foreach ($where as $val){
            if (is_array($val)){
                self::whereType($val);
            }else{
                self::whereType($where);
            }
        }
        //dump(self::$obj->toSql());//调试
        return new static;
    }

    /**
     * 语法拼接
     * $orderBy = [['id'],['time','desc']]
     * $groupBy = ['city','state']
     */
    public static function buildSql($field=[],$orderBy=[],$groupBy=[]){
        if ($field)self::$obj=self::$obj->select($field);
        foreach ($orderBy as $val){
            self::$obj = self::$obj->orderBy($val[0],$val[1]??'asc');
        }
        if ($groupBy)self::$obj=self::$obj->groupBy($groupBy);
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
     * 获取分页数据
     */
    public static function getPage(){
        $limit = request('limit',env('DEFAULT_LIMIT',20));
        $limit = $limit>1000?1000:intval($limit);
        $page  = request('page',1);
        return self::$obj->forPage($page,$limit)->get()->toArray();
    }

    //条件类型
    public static function whereType($val){
        if ($val[1] == 'in'){
            self::$obj = self::$obj->whereIn($val[0],$val[2]);
        }elseif (isset($val[3]) && $val[3] == 'or'){
            self::$obj = self::$obj->orWhere($val[0],$val[1],$val[2]);
        }elseif ($val[1] == 'between'){
            self::$obj = self::$obj->whereBetween($val[0],$val[2]);
        }elseif (isset($val[2]) && $val[2] == 'closure'){//闭包的$query和外层对象不一致，所以重写一遍
            if ($val[0]=='and'){
                self::$obj = self::$obj->where(function ($query) use ($val) {
                    return $this->whereClose($query,$val);
                });
            }elseif ($val[0]=='or'){
                self::$obj = self::$obj->orWhere(function ($query) use ($val) {
                    return $this->whereClose($query,$val);
                });
            }
        }else{
            if (isset($val[2])){
                self::$obj = self::$obj->where($val[0],$val[1],$val[2]);
            }else{
                self::$obj = self::$obj->where($val[0],$val[1]);
            }
        }
    }

    //闭包条件
    public static function whereClose($query,$val){
        foreach ($val[1] as $v){
            if ($v[1] == 'in'){
                $query = $query->whereIn($v[0],$v[2]);
            }elseif (isset($v[3]) && $v[3] == 'or'){
                $query = $query->orWhere($v[0],$v[1],$v[2]);
            }elseif ($val[1] == 'between'){
                $query = $query->whereBetween($v[0],$v[2]);
            }else{
                if (isset($v[2])){
                    $query = $query->where($v[0],$v[1],$v[2]);
                }else{
                    $query = $query->where($v[0],$v[1]);
                }
            }
        }
        return $query;
    }

}
