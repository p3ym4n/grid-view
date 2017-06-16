<?php
/**
 * Created by PhpStorm.
 * User: p3ym4n
 * Date: 1/16/2016 AD
 * Time: 01:07
 * TODO : making the ability to update url (issue in loading js & css)
 */

namespace p3ym4n\GridView;

use App\Admin;
use App\Http\Assets\ClipboardAsset;
use App\Http\Assets\DatePickerAsset;
use App\Http\Assets\PersianDateAsset;
use App\Http\Assets\SelectAsset;
use App\Traits\StatusTrait;
use Auth;
use Exception;
use Facades\p3ym4n\AssetManager\Asset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use p3ym4n\JDate\JDate;

class Grid {
    
    /**
     * search forms static class prefix
     * @var string
     */
    private static $SEARCH_FORM = 'gridSearch';
    
    /**
     * the parent div id suffix
     * @var string
     */
    private static $TOP_SUFFIX = 'Top';
    
    /**
     * the tables prefix
     * @var string
     */
    private static $TABLE_PREFIX = 'table';
    
    /**
     * the grid default query strings prefix
     * @var string
     */
    private static $QUERYSTRING_PREFIX = 'g_';
    
    /**
     * the list of sizes to show
     * @var array
     */
    private static $pageSizeDefault = [10, 20, 50, 100, 200, 300];
    
    /**
     * the name of the key attribute of model
     * @var
     */
    private $key;
    
    /**
     * the base url that forms & grid actions will sent to there
     * @var
     */
    private $url;
    
    /**
     * the name of the models table
     * @var
     */
    private $name;
    
    /**
     * the prefix for grid exclusive search gets
     * @var string
     */
    private $prefix;
    /**
     * the request object
     * @var
     */
    private $request;
    
    /**
     * an array of attributes
     * @var array
     */
    private $attributes;
    
    /** number of columns
     * @var int
     */
    private $countColumns;
    
    /**
     * current page value
     * @var
     */
    private $page;
    
    /**
     * current pageSize value
     * @var
     */
    private $size;
    
    /**
     * current sort by "attribute"
     * @var
     */
    private $sort;
    
    /**
     * current sort mode
     * @var
     */
    private $mode;
    
    /**
     * the results collection
     * @var
     */
    private $body;
    
    /**
     * the model
     * @var
     */
    private $model;
    
    /**
     * holding tools data
     * @var
     */
    private $extra;
    
    /**
     * tools definition
     * @var
     */
    private $tools;
    
    /**
     * bulk actions definition
     * @var
     */
    private $bulks;
    
    /**
     * the grid definition for columns
     * @var array
     */
    private $options = [];
    
    /**
     * grid forms class identifier
     * @var
     */
    private $formName;
    
    /**
     * request input values
     * @var array
     */
    private $gets = [];
    
    /**
     * total size of the table
     * @var
     */
    private $dataCount;
    
    /**
     * total pages based on pageSize
     * @var
     */
    private $pagesCount;
    
    /**
     * helper for making links
     * @var array
     */
    private $linkSearch = [];
    
    /**
     * identifier of the parent div
     * @var string
     */
    private $topDiv = '';
    
    /**
     * identifier of the grid table
     * @var string
     */
    private $topTable = '';
    
    /**
     * the relations for eager load
     * @var array
     */
    private $eagerRelations = [];
    
    /**
     * Grid constructor.
     *
     * @param string $modelClass
     */
    public function __construct($modelClass) {
        $this->request = request();
        $this->setUrl(url()->current());
        $this->setModel($this->instantiate($modelClass));
    }
    
    /**
     * @param string $modelClass name of the model class
     *
     * @return static
     */
    public static function create($modelClass) {
        return new static($modelClass);
    }
    
    /**
     * @param string $modeClass
     *
     * @return Model
     */
    private function instantiate($modeClass) {
        return new $modeClass();
    }
    
    /**
     * adding the relations for eager loading
     */
    public function relationsToLoad() {
        
        $this->eagerRelations = func_get_args();
        
        return $this;
    }
    
    /**
     * adding conditions to model query
     *
     * @param callable $callable
     *
     * @return $this
     */
    public function addCondition(callable $callable) {
        
        $this->model = $callable($this->model);
        
        return $this;
    }
    
    /**
     * the model to use
     *
     * @param Model $model
     *
     * @return Grid $this
     */
    private function setModel($model) {
        
        $this->model = $model;
        $this->setName($model->getTable());
        $this->setKey($model->getKeyName());
        
        return $this;
    }
    
    /**
     * the table name of the
     *
     * @param string $name
     *
     * @return Grid $this
     */
    public function setName($name) {
        
        $this->name     = $name;
        $modifiedName   = studly_case($name);
        $this->formName = static::$SEARCH_FORM . $modifiedName;
        $this->topDiv   = $modifiedName . static::$TOP_SUFFIX;
        $this->topTable = static::$TABLE_PREFIX . $modifiedName;
        
        return $this;
    }
    
    /**
     * the column that used as the model key
     *
     * @param string $key
     *
     * @return Grid $this
     */
    public function setKey($key) {
        
        $this->key = $key;
        
        return $this;
    }
    
    /**
     * @param string $url
     *
     * @return Grid $this
     */
    public function setUrl($url) {
        
        $this->url = $url;
        
        return $this;
    }
    
    /**
     * gets an array of the column with this approach :
     *
     * @param array $options
     *  [
     *  'attribute' => (required) the name of the model attribute ,
     *  'value'     => (optional) a callable function. like : function($model->attribute , $model) ,
     *  'title'     => (optional) the grid column title ,
     *  'filter'    => (optional) a callable function. like : function($_GET['value']))
     *  OR an array with this approach : [
     *  'type' =>   calls a method in this gridView object with this name approach :
     *  $this->{filter . studly_case('type')}( $column  , $list) ,
     *  'list' =>   (optional) an array of arrays with ['id' => 'value']
     *  signature or ['id' , ...] ,
     *  ],
     *  'sort'      => (optional) a boolean value to have sort arrows or not ,
     *  'search'    => (optional) a callable function for model modifying.
     *  like : function($model , $column , $value) ,
     *  'limit'     => (optional) (int) the number of the string length to cut ,
     *  'width'     => (optional) (int) the width of the column in gird table ,
     *  ]
     *
     * @return $this
     * @throws Exception
     */
    public function setColumns(array $options) {
        
        foreach ($options as $option) {
            
            //checking that option not empty
            if ( ! empty($option)) {
                
                //checking that attribute item has been defined or not
                if ( ! isset($option['attribute']) || empty($option['attribute'])) {
                    throw new Exception('options passed to ' . __METHOD__ . ' method should have a filled item with "attribute" key .');
                }
                
                if (isset($this->options[$option['attribute']])) {
                    throw new Exception('there is a duplicate definition of "' . $option['attribute'] . '" attribute in ' . __METHOD__ . ' .');
                }
                
                //checking for title
                if ( ! isset($option['title'])) {
                    $option['title'] = $option['attribute'];
                }
                
                //checking for value callable and making it if not present
                if ( ! isset($option['value'])) {
                    $option['value'] = function ($value, Model $model) {
                        return $value;
                    };
                }
                
                //checking for search form filter callable
                if ( ! isset($option['filter'])) {
                    $option['filter'] = function ($value) use ($option) {
                        $placeHolder = '';
                        if (isset($option['placeholder']) && ! empty($option['placeholder'])) {
                            $placeHolder = ' placeholder="' . $option['placeholder'] . '" ';
                        }
                        return '<input type="search" class="form-control" dir="auto" name="' . $option['attribute'] . '" ' . $placeHolder . '  value="' . $value . '" >';
                    };
                    
                } elseif ($option['filter'] == false) {
                    $option['filter'] = function ($value) use ($option) {
                        $placeHolder = '';
                        if (isset($option['placeholder']) && ! empty($option['placeholder'])) {
                            $placeHolder = ' placeholder="' . $option['placeholder'] . '" ';
                        }
                        return '<input type="search" class="form-control" dir="auto" ' . $placeHolder . '  value="' . $value . '" disabled="disabled" >';
                    };
                    
                } elseif (is_array($option['filter'])) {
                    $method = 'filter' . studly_case($option['filter']['type']);
                    if (method_exists($this, $method)) {
                        $option['filter'] = function ($value) use ($option, $method) {
                            unset($option['filter']['type']);
                            return $this->$method($option['attribute'], $option['filter'], $value);
                        };
                        
                    } else {
                        throw new Exception('"' . $method . '" method not exist in ' . __CLASS__ . ' .');
                    }
                }
                
                //checking for sort existence
                if ( ! isset($option['sort'])) {
                    $option['sort'] = true;
                }
                
                //checking the search for model query
                if ( ! isset($option['search'])) {
                    $option['search'] = function ($model, $column, $value) {
                        if (is_numeric($value)) {
                            return $model->where($column, $value);
                        }
                        return $model->where($column, 'like', '%' . $value . '%');
                    };
                    
                } elseif (is_string($option['search'])) {
                    
                    $method = 'search' . studly_case($option['search']);
                    if (method_exists($this, $method)) {
                        $option['search'] = function ($model, $column, $value) use ($method) {
                            return $this->$method($model, $column, $value);
                        };
                    } else {
                        throw new Exception('"' . $method . '" method not exist in ' . __CLASS__ . ' .');
                    }
                }
                
            }
            
            //checking for length limit
            if ( ! isset($option['limit'])) {
                $option['limit'] = 0;
            }
            
            //column width
            if ( ! isset($option['width'])) {
                $option['width'] = 0;
            }
            
            $this->options[$option['attribute']] = [
                'title'  => $option['title'],
                'value'  => $option['value'],
                'filter' => $option['filter'],
                'sort'   => (boolean) $option['sort'],
                'search' => $option['search'],
                'limit'  => (int) $option['limit'],
                'width'  => (int) $option['width'],
            ];
        }
        
        return $this;
    }
    
    /**
     * gets an array of the tools with this approach : $name => $callable($model , $this)
     *
     * @param array $tools
     *
     * @return $this
     */
    public function setTools(array $tools) {
        
        $this->tools = $tools;
        
        return $this;
    }
    
    /**
     * gets an array of the bulk action and make a form for every item in it
     * at the bottom of the grid.
     * every item have approach like this :
     * $name => [
     *  'route' => string for the form action attribute ,
     *  'method' => string the http method,
     *  'button' => callable the view of the button
     * ]
     *
     * @param array $bulks
     *
     * @return $this
     */
    public function setBulks(array $bulks = []) {
        
        $this->bulks = $bulks;
        
        return $this;
    }
    
    /**
     * make the options needed for key column to show
     * @return array|boolean
     */
    private function keyColumnOptionCheck() {
        
        if ($this->key) {
            
            $keyOption = [];
            if (isset($this->options[$this->key])) {
                $keyOption = $this->options[$this->key];
                unset($this->options[$this->key]);
            }
            
            //title modification
            $title = '<p>' . trans('grid.key') . '</p>';
            if (isset($keyOption['title'])) {
                $title = '<p>' . $keyOption['title'] . '</p>';
            }
            if ( ! empty($this->bulks)) {
                $title = '<div class="checkbox">
							<label>
								<input type="checkbox" autocompelete="off" data-table="' . $this->name . '" value="1" name="maincheck"  class="mainCheck" />
								' . trans('grid.choose all') . '
							</label>
						</div>';
            }
            $keyOption['title'] = $title;
            
            //value modification
            if (isset($keyOption['value'])) {
                unset($keyOption['value']);
            }
            
            $keyOption = array_merge([
                'title'  => $title,
                'value'  => function ($value, Model $model) {
                    if ( ! empty($this->bulks)) {
                        $value = '<input type="checkbox" value="' . $value . '" name="' . $this->key . '[]" class="check" data-table="' . $this->name . '" autocomplete="off"><br />' . $value;
                    }
                    return '<label>' . $value . '</label>';
                },
                'filter' => function ($value) {
                    return '<div class="form-group"><input type="number" min="1" placeholder="' . trans('grid.number') . '" class="form-control text-center" name="' . $this->key . '" value="' .
                           $value . '" ></div>';
                },
                'sort'   => true,
                'search' => function ($model, $column, $value) {
                    return $model->where($column, $value);
                },
                'limit'  => 0,
                'width'  => 0,
            ], $keyOption);
            
            //prePend it to $this->options
            $this->options = [$this->key => $keyOption] + $this->options;
        }
    }
    
    /**
     * loading and initializing some value and attributes
     */
    private function bootstrap() {
        
        //checking for key column definition
        $this->keyColumnOptionCheck();
        
        //listing the attributes
        $this->attributes = array_keys($this->options);
        
        //calculating the columns length
        $this->countColumns = count($this->attributes);
        if ( ! empty($this->tools)) {
            $this->countColumns++;
        }
        
        //getting the request inputs
        $this->gets = $this->request->only($this->attributes);
        
        //checking for intersection
        if (count(array_intersect($this->attributes, ['page', 'size', 'sort', 'mode']))) {
            $this->prefix = static::$QUERYSTRING_PREFIX;
        }
        $this->page = $this->request->input($this->prefix . 'page', 1);
        $this->size = $this->request->input($this->prefix . 'size', static::$pageSizeDefault[0]);
        $this->sort = $this->request->input($this->prefix . 'sort', $this->key);
        $this->mode = $this->request->input($this->prefix . 'mode', 'desc');
        
        //defaults prevents for making long query string by adding default values
        $defaults = [];
        if ($this->page != 1) {
            $defaults[$this->prefix . 'page'] = $this->page;
        }
        if ($this->size != static::$pageSizeDefault[0]) {
            $defaults[$this->prefix . 'size'] = $this->size;
        }
        if ($this->sort != $this->key) {
            $defaults[$this->prefix . 'sort'] = $this->sort;
        }
        if ($this->mode != 'desc') {
            $defaults[$this->prefix . 'mode'] = $this->mode;
        }
        
        //making the link search property
        $this->linkSearch = array_replace_recursive($this->gets, $defaults);
    }
    
    /**
     * gets all the needed data for show
     * @return array
     * @throws Exception
     */
    public function render() {
        
        //initializing some values
        $this->bootstrap();
        
        foreach ($this->options as $attribute => $option) {
            
            //checking for value existence
            $get = isset($this->gets[$attribute]) ? $this->gets[$attribute] : null;
            
            //running the filter callback
            $option['form'] = $option['filter']($get);
            unset($option['filter']);
            
            //running the search callback
            if ( ! is_null($get)) {
                $this->model = $option['search']($this->model, $attribute, $get);
            }
            unset($option['search']);
            
            if ($option['sort'] !== false) {
                
                //checking sorting
                $up   = 'fa-angle-up';
                $down = 'fa-angle-down';
                if ($this->sort == $attribute) {
                    if ($this->mode == 'asc') {
                        $up = 'fa-chevron-up';
                    } else {
                        $down = 'fa-chevron-down';
                    }
                }
                
                $option['up']       = $up;
                $option['upLink']   = $this->makeLink([
                    $this->prefix . 'sort' => $attribute,
                    $this->prefix . 'mode' => 'asc',
                ]);
                $option['down']     = $down;
                $option['downLink'] = $this->makeLink([
                    $this->prefix . 'sort' => $attribute,
                    $this->prefix . 'mode' => 'desc',
                ]);
                
            } elseif ($this->sort == $attribute) {
                if ($this->key) {
                    $this->sort = $this->key;
                }
            }
            
            $this->options[$attribute] = $option;
        }
        
        //count all records
        $this->dataCount = $this->model->count();
        
        //confirm that this page is the last page in results
        $temp = ceil($this->dataCount / $this->size);
        if ($this->page > $temp) {
            if ($temp != 0) {
                $this->page = $temp;
            }
        }
        
        //adding eager relations
        $this->model = $this->model->with($this->eagerRelations);
        
        //checking for sort
        if ($this->sort) {
            $this->model = $this->model->orderBy($this->sort, $this->mode);
        }
        //offset & limit
        $this->model = $this->model->skip(($this->page - 1) * $this->size);
        $this->model = $this->model->take($this->size);
        
        //getting the records
        $data = $this->model->get();
        
        //if there are any records
        if ($this->dataCount > 0) {
            foreach ($data as $key => $subModel) {
                foreach ($this->options as $attribute => $option) {
                    
                    $value = $option['value']($subModel->getAttribute($attribute), $subModel);
                    
                    //check for substring
                    if ($option['limit'] > 0) {
                        $value = strip_tags($value);
                        $value = '<p class="tooltips" title="' . str_limit($value, 200) . '" >' . str_limit($value, (int) $option['limit']) . '</p>';
                    }
                    
                    $this->body[$key][] = $value;
                }
                
                //call the record tools 1 by 1 for use in body
                if ( ! empty($this->tools)) {
                    $toolsBox = [];
                    foreach ($this->tools as $tool) {
                        $toolsBox[] = $tool($subModel);
                    }
                    $this->extra[$key]['tools'] = implode(' ', $toolsBox);
                }
            }
        }
        
        //adding styles
        Asset::addStyle("
            .table-grid{
                margin-bottom: 0px!important;
            }
            .table-grid th{
                background-image: -webkit-linear-gradient(top, #f5f5f5 0%, #e8e8e8 100%);
                background-image:      -o-linear-gradient(top, #f5f5f5 0%, #e8e8e8 100%);
                background-image: -webkit-gradient(linear, left top, left bottom, from(#f5f5f5), to(#e8e8e8));
                background-image:         linear-gradient(to bottom, #f5f5f5 0%, #e8e8e8 100%);
                filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#fff5f5f5', endColorstr='#ffe8e8e8', GradientType=0);
                background-repeat: repeat-x;
                font-weight: 500;
                font-size: 13px;
                width: auto;
                max-width: 300px!important;
            }
            .table-grid th .checkbox{
                margin-bottom: 7px;
            }
            .table-grid td label{
                display: block;
                cursor: pointer;
            }
            .table-grid th label{
                font-weight: 500;
                font-size: 13px;
                line-height: 20px;
            }
            .table-grid thead th:last-child{
                min-width: 90px!important;
                width: auto;
            }
            .table-grid thead th:last-child .btn{
                padding: 4px!important;
                margin-bottom: 5px;
                margin-top: 1px;
            }
            .table-grid tbody form{
                margin-bottom: 0;
            }
            .table-grid th .form-control{
                font-weight: 400;
                line-height: 30px;
            }
            .table-grid th .form-control.btn-group,
            .table-grid th .form-group{
                margin-bottom: 0px;
            }
            .table-grid .th-form .form-control:not(.btn-group){
                padding-right: 3px;
                padding-left: 3px;
            }
            .th-sort .fa-chevron-up,
            .th-sort .fa-chevron-down,
            .th-sort .fa-angle-down,
            .th-sort .fa-angle-up,
            .th-sort .fa-refresh,
            .th-sort .fa-refresh,
            .th-sort .fa-search{
                font-size: 20px;

            }
            .th-sort .height-justified{
                height: 13px;
            }
            .th-sort .fa:active,
            .th-sort .fa:hover{
                text-decoration: none;
            }
            .table-grid tr.selected:hover td,
            .table-grid tr.selected td{
                background-color: #FFF6B2;
            }
            .table-grid td{
                vertical-align: middle!important;
                text-align: center;
                padding: 1px!important;
                height: 40px;
                width: auto;
                max-width: 300px!important;
                font-size: 14px;
            }
            .table-grid .med-height{
                line-height: 24px;
            }
            .table-grid .countSpan{
                margin: 0;
                font-weight: normal;
            }
            .table-grid td:last-child .btn{
                padding: 1px;
			    min-width: calc(33% - 9px);
			    margin: 2px 2px;
			    float: right;
			    max-width: calc(50% - 9px);
			    width: 28%;
            }
            
            .table-grid td:last-child .btn .fa{
                font-size: 1.1em;
			    line-height: .55em;
			    vertical-align: -20%;
            }
            
            .gridPage{
                float: left;
                margin-right: 20px;
                position: relative;
            }
            .gridPage form{
                display: inline-block;
                margin-right: 5px;
                margin-left: 5px;
            }

            .gridPage .form-control{
                width: 50px!important;
                padding: 1px;
                text-align: center;
            }
            .grid-sizer {
                float: left;
            }
            .grid-sizer .control-label{
                margin-left: 20px;
                position: relative;
                top: 7px;
            }

            .grid-sizer .btn-group{
                width: 70px!important;
                float: left!important;
            }
            .table-grid .bulkForm{
                display: none;
                float: right;
                margin-left: 15px;
            }
            .table-grid .bulkForm .btn-group{
                margin-left: 5px;
            }
            .table-grid hr{
                margin-top: 5px;
                margin-bottom: 5px;
            }
            .table-grid .bootstrap-select .btn{
                padding-left: 10px;
            }
            .table-grid .bootstrap-select .btn .caret{
                left: 5px!important;
            }
            .table-grid .grid-order{
                margin:0 auto;
                max-width: 90px;
                min-width: 50px;
            }
           
        ");
        
        //adding scripts
        Asset::addScript("$(function () {

				//this grid main holder
				//var {$this->topDiv}Holder = $('#{$this->topDiv}');
				var {$this->topDiv}Holder = $('#inWrapper');
				var {$this->topDiv}Defaults = {
					page : 1,
					size : " . static::$pageSizeDefault[0] . ",
					sort : '{$this->key}',
					mode : 'desc',
					maincheck : 1
				};

				//gridLoad links
				$('#{$this->topDiv} a.gridLoad').unbind('click').click(function (e) {
					e.preventDefault();
					var link = $(this).attr('href');
					pushState(link);
					$.ajax({
						type: 'get',
						url: link,
						beforeSend: function(){
							{$this->topDiv}Holder.spin();
						},
						complete: function (data) {
						
							{$this->topDiv}Holder.spin(false).html(data.responseText);
							scrollTop({$this->topDiv}Holder);
						}
					});
				});

				//ajax search
				$('#{$this->topTable} .{$this->formName}').unbind('submit').submit(function (e) {
					var data = [];
					$('#{$this->topTable} .{$this->formName} :input').each(function () {
						var temp = $(this).val();
						if (temp != '' && temp != null ) {
							var tempName = $(this).attr('name');
							if({$this->topDiv}Defaults[tempName] != temp){
								data.push(tempName + '=' + temp);
							}
						}
					});
					var link = $(this).attr('action');
					if (data.length > 0) {
						link += (link.indexOf(\"?\") === -1 ? '?' : '&') + decodeURI(data.join('&'));
					}
					pushState(link);
					$.ajax({
						type: 'get',
						url: link,
						beforeSend: function(){
							{$this->topDiv}Holder.spin();
						},
						complete: function (data) {
							{$this->topDiv}Holder.spin(false).html(data.responseText);
							scrollTop({$this->topDiv}Holder);
						}
					});
					return false;
				});

				//bulk forms
				$('#{$this->topTable} .{$this->formName}Bulk').unbind('submit').submit(function () {

					var tableSerialize = $('#{$this->topTable} tbody :input').serialize();
					if (tableSerialize.length > 0) {
						tableSerialize = '&' + tableSerialize;
					}
					var data = $(this).serialize() + tableSerialize;
					var link = $(this).attr('action');
					
					$.ajax({
						type: 'post',
						url: decodeURI(link),
						data: data,
						beforeSend: function(){
							{$this->topDiv}Holder.spin().children('.alert').remove();
						},
						success: function (data) {
							$('.tooltip , .popover').remove();
							{$this->topDiv}Holder.spin(false).prepend(data);
							scrollTop({$this->topDiv}Holder);
							
						},
						error : function(data){
							$('.tooltip , .popover').remove();
							{$this->topDiv}Holder.spin(false).prepend(showMessage(data));
							scrollTop({$this->topDiv}Holder);
						}
					});
					return false;
				});

				//page size select
				$('#{$this->topTable} .{$this->formName} select').unbind('change').change(function () {
					$(this).parents('form').trigger('submit');
				});

				//checkboxes select
				$('#{$this->topTable} .mainCheck').unbind('change').change(function () {
					var table = $('#{$this->topTable}');
					if ($(this).is(':checked')) {
						table.find('.check').prop('checked', true).trigger('change');
						var bottom = {$this->topDiv}Holder.position().top + {$this->topDiv}Holder.outerHeight(true);
						scrollTop(bottom);
					} else {
						table.find('.check').prop('checked', false).trigger('change');
					}
				});

				//row select active class
				$('#{$this->topTable} .check').unbind('change').change(function () {
					if ($(this).is(':checked')) {
						$(this).parent().parent().parent().addClass('selected');
					} else {
						$(this).parent().parent().parent().removeClass('selected');
					}

					var table = $('#{$this->topTable}');
					if ($('#{$this->topTable} .check:checked').length > 0) {
						if ($('#{$this->topTable} .check:checked').length < $('#{$this->topTable} .check').length) {
							$('#{$this->topTable} .mainCheck').prop('indeterminate', true);
						} else {
							$('#{$this->topTable} .mainCheck').prop('checked', true).prop('indeterminate', false);
						}
						table.find('.bulkForm').fadeIn();
					} else {
						$('#{$this->topTable} .mainCheck').prop('indeterminate', false);
						table.find('.bulkForm').fadeOut();
					}
				});
			});
        ");
        
        return [
            'pk'           => $this->key,
            'topDiv'       => $this->topDiv,
            'topTable'     => $this->topTable,
            'bulks'        => $this->bulkActions(),
            'name'         => $this->name,
            'formName'     => $this->formName,
            'url'          => $this->url,
            'gets'         => $this->gets,
            'prefix'       => $this->prefix,
            'options'      => $this->options,
            'tools'        => $this->tools,
            'dataCount'    => $this->dataCount,
            'body'         => $this->body,
            'extra'        => $this->extra,
            'countColumns' => $this->countColumns,
            'pagination'   => $this->pagination(),
            'page'         => $this->page,
            'pagesCount'   => $this->pagesCount,
            'pageSize'     => $this->pageSize(),
            'sort'         => $this->sort,
            'mode'         => $this->mode,
        ];
    }
    
    /**
     * iterates over the bulk actions defined before for render() method
     * @return array|bool
     */
    private function bulkActions() {
        
        if ($this->dataCount > 0 && ! empty($this->bulks) && $this->key != false) {
            $out = [];
            foreach ($this->bulks as $bulk) {
                $temp = '';
                if (isset($bulk['select'])) {
                    $temp .= $bulk['select']($this);
                }
                if (isset($bulk['button'])) {
                    $temp .= $bulk['button']($this);
                }
                if (isset($bulk['method'])) {
                    $temp .= '<input type="hidden" name="_method" value="' . $bulk['method'] . '" />';
                }
                $route = $this->url;
                if (isset($bulk['route'])) {
                    $route = $bulk['route'];
                }
                $out[] = [
                    'route' => $route,
                    'form'  => $temp,
                ];
            }
            
            return empty($out) ? false : $out;
        } else {
            return false;
        }
    }
    
    /**
     * makes the pagination html part of the render() method
     * @return array|bool
     */
    private function pagination() {
        
        $this->pagesCount = ceil($this->dataCount / $this->size);
        if ($this->pagesCount == 1 || $this->dataCount == 0) {
            return false;
        } else {
            
            $out = [];
            if ($this->page >= 5) {
                $out[] = ['key' => '<span class="fa fa-lg fa-angle-double-right"></span>', 'value' => $this->makeLink(['page' => 1]), 'class' => null];
            }
            if ($this->page != 1) {
                $out[] = ['key' => '<span class="fa fa-lg fa-angle-right"></span>', 'value' => $this->makeLink(['page' => ($this->page - 1)]), 'class' => null];
            }
            if ($this->pagesCount < 8) {
                for ($i = 1; $i <= $this->pagesCount; $i++) {
                    if ($this->page == $i) {
                        $out[] = ['key' => $i, 'value' => $this->makeLink(['page' => $i]), 'class' => 'active'];
                    } else {
                        $out[] = ['key' => $i, 'value' => $this->makeLink(['page' => $i]), 'class' => null];
                    }
                }
            } else {
                if ($this->page <= 4) {
                    for ($i = 1; $i <= 7; $i++) {
                        if ($this->page == $i) {
                            $out[] = ['key' => $i, 'value' => $this->makeLink(['page' => $i]), 'class' => 'active'];
                        } else {
                            $out[] = ['key' => $i, 'value' => $this->makeLink(['page' => $i]), 'class' => null];
                        }
                    }
                    $out[] = ['key' => '...', 'value' => 'javascript:void(0)', 'class' => 'disabled'];
                } elseif (($this->pagesCount - $this->page) <= 4) {
                    $out[] = ['key' => '...', 'value' => 'javascript:void(0)', 'class' => 'disabled'];
                    for ($i = ($this->page - 2); $i <= $this->pagesCount; $i++) {
                        if ($this->page == $i) {
                            $out[] = ['key' => $i, 'value' => $this->makeLink(['page' => $i]), 'class' => 'active'];
                        } else {
                            $out[] = ['key' => $i, 'value' => $this->makeLink(['page' => $i]), 'class' => null];
                        }
                    }
                } else {
                    $out[] = ['key' => '...', 'value' => 'javascript:void(0)', 'class' => 'disabled'];
                    for ($i = ($this->page - 2); $i <= ($this->page + 2); $i++) {
                        if ($this->page == $i) {
                            $out[] = ['key' => $i, 'value' => $this->makeLink(['page' => $i]), 'class' => 'active'];
                        } else {
                            $out[] = ['key' => $i, 'value' => $this->makeLink(['page' => $i]), 'class' => null];
                        }
                    }
                    $out[] = ['key' => '...', 'value' => 'javascript:void(0)', 'class' => 'disabled'];
                }
            }
            
            if ($this->pagesCount != $this->page) {
                $out[] = ['key' => '<span class="fa fa-lg fa-angle-left"></span>', 'value' => $this->makeLink(['page' => ($this->page + 1)]), 'class' => null];
            }
            if (($this->pagesCount - $this->page) >= 5) {
                $out[] = ['key' => '<span class="fa fa-lg fa-angle-double-left"></span>', 'value' => $this->makeLink(['page' => $this->pagesCount]), 'class' => null];
            }
            
            return $out;
        }
    }
    
    /**
     * makes the pageSize html part of the render() method
     * @return array|bool
     */
    private function pageSize() {
        
        if ($this->dataCount > static::$pageSizeDefault[0]) {
            $out = [];
            foreach (static::$pageSizeDefault as $v) {
                if ($this->dataCount > $v) {
                    if ($v == $this->size) {
                        $out[] = ['key' => $v, 'value' => $v, 'selected' => true];
                    } else {
                        $out[] = ['key' => $v, 'value' => $v, 'selected' => false];
                    }
                }
            }
            
            return $out;
        } else {
            return false;
        }
    }
    
    /**
     * make a query string of grid conditions merged with $merge
     *
     * @param array $merge
     *
     * @return string
     */
    private function makeLink($merge) {
        
        $params = array_replace_recursive($this->linkSearch, $merge);
        
        if (isset($params['page']) && $params['page'] == 1) {
            unset($params['page']);
        }
        if (isset($params['size']) && $params['size'] == static::$pageSizeDefault[0]) {
            unset($params['size']);
        }
        if (isset($params['sort']) && $params['sort'] == $this->key) {
            unset($params['sort']);
        }
        if (isset($params['mode']) && $params['mode'] == 'desc') {
            unset($params['mode']);
        }
        
        $params = urldecode(http_build_query($params));
        
        if ( ! empty($params)) {
            $params = '?' . $params;
        }
        
        return $this->url . $params;
    }
    
    /**
     * This is one of the filter methods that returns a html select for search
     *
     * @param string      $name
     * @param array       $data
     * @param null|string $value
     *
     * @return string
     */
    private function filterSelect($name, array $data = [], $value) {
        
        //add the bootstrap-select asset
        SelectAsset::add();
        
        //if it is a closure we call the function
        $list = isset($data['list']) ? value($data['list']) : [];
        
        //checking for any new prompts in select
        $prompt = isset($data['prompt']) ? $data['prompt'] : trans('grid.select one');
        $empty  = ($list instanceof Collection) ? $list->isEmpty() : (empty($list) ? true : false);
        
        if ( ! $empty) {
            $out = '<select name="' . $name . '" class="form-control" >
                        <option value="" >' . $prompt . '</option>';
            foreach ($list as $key => $item) {
                
                $style    = '';
                $disabled = '';
                if (is_array($item)) {
                    $key = $item['id'];
                    unset($item['id']);
                    
                    //checking for intentions
                    if (isset($item['indent'])) {
                        $style = ' style="padding-right:' . (int) $item['indent'] . 'px;" ';
                        unset($item['indent']);
                    }
                    
                    //checking for disabled options
                    if (isset($item['disabled']) && $item['disabled'] == true) {
                        $disabled = ' disabled="disabled" ';
                        unset($item['disabled']);
                    }
                    $item = implode(' ', $item);
                }
                
                $selected = ((string) $value == (string) $key) ? ' selected="selected" ' : '';
                $icon     = isset($data['icons'][$key]) ? 'data-icon="' . $data['icons'][$key] . '"' : '';
                
                $out .= '<option ' . $style . ' ' . $icon . ' value="' . $key . '" ' . $disabled . ' ' . $selected . ' >' . $item . '</option>';
            }
            $out .= '</select>';
        } else {
            $out = '<select class="form-control" disabled="disabled" >
                        <option value="" >' . trans('words.empty list') . '</option>
                    </select>';
        }
        
        return $out;
    }
    
    /**
     * this is one of the filter methods that returns a html multiple select for search
     *
     * @param string      $name
     * @param array       $data
     * @param string|null $value
     *
     * @return string
     */
    private function filterMultipleSelect($name, array $data = [], $value) {
        
        //add the bootstrap-select asset
        SelectAsset::add();
        
        //if it is a closure we call the function
        $list = isset($data['list']) ? value($data['list']) : [];
        
        if ( ! empty($list)) {
            
            $out   = '<select multiple="multiple" name="' . $name . '" class="form-control" >';
            $value = explode(',', $value);
            
            foreach ($list as $key => $item) {
                
                if (is_array($item)) {
                    $key = $item['id'];
                    unset($item['id']);
                    $item = implode(' ', $item);
                }
                
                $selected = in_array((string) $key, $value) ? ' selected="selected" ' : '';
                $icon     = isset($data['icons'][$key]) ? 'data-icon="' . $data['icons'][$key] . '"' : '';
                
                $out .= '<option ' . $icon . ' value="' . $key . '" ' . $selected . ' >' . $item . '</option>';
            }
            $out .= '</select>';
        } else {
            $out = '<select class="form-control" disabled="disabled" >
                        <option value="" >' . trans('words.empty list') . '</option>
                    </select>';
        }
        
        return $out;
    }
    
    /**
     * This method applies the $this->filterMultipleSelect search query on $this->Model
     *
     * @param Model       $model
     * @param string      $name
     * @param string|null $value
     *
     * @return Model
     */
    private function searchMultipleSelect(Model $model, $name, $value) {
        if ($value !== null) {
            $model = $model->whereIn($name, $value);
        }
        return $model;
    }
    
    /**
     * This is one of the filter methods that's return 2 inputs with datepickers
     *
     * @param string      $name
     * @param array       $data
     * @param string|null $value
     *
     * @return string
     */
    private function filterDatePicker($name, array $data = [], $value) {
        $sets = [];
        if ($value === null) {
            $value = [];
        }
        if (isset($value['s']) && $value['s'] != '') {
            $dates = explode('-', $value['s']);
            if ( ! isset($dates[1])) {
                $dates[1] = '00:00:00';
            }
            list($year, $month, $day) = explode('/', $dates[0]);
            list($hour, $minute, $second) = explode(':', $dates[1]);
            $sets['s'] = [$year, $month, $day, $hour, $minute, $second];
        }
        
        if (isset($value['e']) && $value['e'] != '') {
            $dates = explode('-', $value['e']);
            if ( ! isset($dates[1])) {
                $dates[1] = '00:00:00';
            }
            list($year, $month, $day) = explode('/', $dates[0]);
            list($hour, $minute, $second) = explode(':', $dates[1]);
            $sets['e'] = [$year, $month, $day, $hour, $minute, $second];
        }
        
        PersianDateAsset::add();
        DatePickerAsset::add();
        Asset::addScript("
            $('.gridDatePicker').each(function(){
                var element = $(this);
                var tempCheck = element.val();
                element.pDatepicker({
                    timePicker: {
                        enabled: true
                    },
                    navigator : {
                        text: {
                            btnNextText: \"<i class='fa fa-lg fa-angle-left'></i>\",
                            btnPrevText: \"<i class='fa fa-lg fa-angle-right'></i>\"
                        },
                    },
                    formatter: function (unixDate) {
                        var pdate = new persianDate(unixDate);
                        pdate.formatPersian = false;
                        return pdate.format('YYYY/MM/DD-HH:mm:ss');
                    },
                    toolbox: {
                        text: {
                            btnToday: \"" . trans('words.today') . "\"
                        }
                    },
                });
                if(tempCheck.length == 0){
                    element.val('');
                } else {
                    element.pDatepicker(\"setDate\", element.data('value'));
                }
            });
        ");
        
        return '<input dir="ltr" type="text" name="' . $name . '[s]"  ' .
               (isset($value['s']) ? 'value="' . $value['s'] . '" data-value="' . json_encode($sets['s'], JSON_NUMERIC_CHECK) . '" ' : '') .
               ' class="form-control gridDatePicker text-center pull-right" style="width:49%" placeholder="از" >
                <input dir="ltr" type="text" name="' . $name . '[e]" ' .
               (isset($value['e']) ? 'value="' . $value['e'] . '" data-value="' . json_encode($sets['e'], JSON_NUMERIC_CHECK) . '" ' : '') .
               ' class="form-control gridDatePicker text-center pull-left" style="width:49%" placeholder="تا" >';
    }
    
    /**
     * This method applies the $this->filterDatePicker search query on $this->Model
     *
     * @param Model       $model
     * @param string      $name
     * @param string|null $value
     *
     * @return Model
     */
    private function searchDatePicker(Model $model, $name, $value) {
        if ($value !== null) {
            $start = null;
            $end   = null;
            
            if (isset($value['s']) && $value['s'] != '') {
                $dates = explode('-', $value['s']);
                if ( ! isset($dates[1])) {
                    $dates[1] = '00:00:00';
                }
                $start = implode(' ', $dates);
            }
            
            if (isset($value['e']) && $value['e'] != '') {
                $dates = explode('-', $value['e']);
                if ( ! isset($dates[1])) {
                    $dates[1] = '00:00:00';
                }
                $end = implode(' ', $dates);
            }
            
            if ($start !== null && $end === null) {
                $model = $model->where(
                    $name,
                    '>=',
                    JDate::createFromFormat(FORMAT_FULL_DATE, $start)->carbon);
            } elseif ($start === null && $end !== null) {
                $model = $model->where(
                    $name,
                    '<',
                    JDate::createFromFormat(FORMAT_FULL_DATE, $end)->carbon
                );
            } elseif ($start !== null && $end !== null) {
                $model = $model->whereBetween($name, [
                    JDate::createFromFormat(FORMAT_FULL_DATE, $start)->carbon,
                    JDate::createFromFormat(FORMAT_FULL_DATE, $end)->carbon,
                ]);
            }
        }
        return $model;
    }
    
    /**
     * @param string      $column
     * @param string|null $title
     * @param string      $username
     *
     * @return array
     */
    public static function column_admin($column = 'admin_id', $title = null, $username = 'username') {
        if (is_null($title)) {
            $title = trans('validation.attributes.' . $column);
        }
        $out = [
            'attribute' => $column,
            'title'     => $title,
            'filter'    => [
                'type' => 'select',
                'list' => Admin::all()->pluck($username, 'id'),
            ],
            'value'     => function ($value, $model) use ($username) {
                if ( ! is_null($model->admin)) {
                    return link_to_action('Admin\BulkController@index', $model->admin->{$username}, ['id' => $model->admin->id], [
                        'class'       => 'load btn btn-link',
                        'data-holder' => '#inWrapper',
                    ]);
                }
            },
        ];
        
        if ( ! Auth::user()->is_main) {
            unset($out['filter']);
            $out['search'] = false;
            $out['value']  = function ($value, $model) {
                if ($model->admin) {
                    return $model->admin->username;
                }
            };
        }
        
        return $out;
    }
    
    /**
     * @param string      $column
     * @param string|null $title
     *
     * @return array
     */
    public static function column_date($column, $title = null) {
        if (is_null($title)) {
            $title = trans('validation.attributes.' . $column);
        }
        return [
            'attribute' => $column,
            'title'     => $title,
            'filter'    => [
                'type' => 'datePicker',
            ],
            'search'    => 'datePicker',
            'value'     => function ($value) {
                if ($value === null) {
                    return trans('words.never');
                } else {
                    return JDate::createFromCarbon($value)->smart();
                }
            },
        ];
    }
    
    /**
     * @param string|null $title
     *
     * @return array
     */
    public static function column_created_at($title = null) {
        return static::column_date('created_at', $title);
    }
    
    /**
     * @param string|null $title
     *
     * @return array
     */
    public static function column_updated_at($title = null) {
        return static::column_date('updated_at', $title);
    }
    
    /**
     * @param string|null $model
     * @param string|null $title
     *
     * @return array
     */
    public static function column_status($model = null, $title = null) {
        if (is_null($model)) {
            $model = StatusTrait::class;
        }
        if (is_null($title)) {
            $title = trans('validation.attributes.status');
        }
        return [
            'attribute' => 'status',
            'title'     => $title,
            'value'     => function ($value) use ($model) {
                return $model::getStatusIcon($value);
            },
            'filter'    => [
                'type'  => 'select',
                'list'  => $model::getStatusList(),
                'icons' => $model::getStatusList('icon'),
            ],
        ];
    }
    
    /**
     * @param string      $column
     * @param string|null $title
     *
     * @return array
     */
    public static function column_order($column = 'order', $title = null) {
        if (is_null($title)) {
            $title = trans('validation.attributes.' . $column);
        }
        return [
            'attribute' => $column,
            'title'     => $title,
            'value'     => function ($value, $model) use ($column) {
                return '<input type="number" dir="ltr" min="0" class="form-control grid-order" name="' . $column . '[' . $model->id . ']' . '" value="' . $value . '">';
            },
        ];
    }
    
    /**
     * @param string      $column
     * @param string|null $title
     *
     * @return array
     */
    public static function column_pic($column = 'pic', $title = null) {
        
        if (is_null($title)) {
            $title = trans('validation.attributes.' . $column);
        }
        return [
            'attribute' => $column,
            'title'     => $title,
            'filter'    => false,
            'sort'      => false,
            'value'     => function ($value, $model) use ($column) {
                if ( ! empty($value)) {
                    $text   = isset($model->title) ? $model->title : $column;
                    $action = action('Library\PreviewController@preview', [
                        'storage' => 'public',
                        'file'    => $value,
                        'static'  => true,
                    ]);
                    return '<a href="' . $action . '" class="ajax" data-holder="#inWrapper" data-method="get" >
                                <img src="' . config('filesystems.disks.local.url') . $value . '" alt="' . $text . '" style="max-height: 70px;" >
                            </a>';
                }
            },
        ];
    }
    
    /**
     * @param string      $column
     * @param string|null $title
     *
     * @return array
     */
    public static function column_link($column = 'link', $title = null) {
        ClipboardAsset::add();
        Asset::addScript("
			//clipboard
			var cb = new Clipboard('.link-copy');
			cb.on('success', function(e) {
			    e.clearSelection();
				$(e.trigger).attr('data-original-title' , 'کپی شد.').tooltip('show');
				setTimeout(function(){
					$(e.trigger).attr('data-original-title' , 'کپی').tooltip('hide')
				},2000);

			}).on('error', function(e) {
				$(e.trigger).attr('data-original-title' , 'مشکلی در کپی بوجود آمد!').tooltip('show');
				setTimeout(function(){
					$(e.trigger).attr('data-original-title' , 'کپی').tooltip('hide')
				},2000);
			});
		");
        Asset::addStyle("
			.link-copy{
				width: 80%;
				min-width: 70px;
				max-width: 200px;
				white-space: nowrap;
				direction: ltr;
				cursor: pointer;
				margin-bottom: 0px;
			}
		");
        if (is_null($title)) {
            $title = trans('validation.attributes.' . $column);
        }
        return [
            'attribute' => $column,
            'title'     => $title,
            'value'     => function ($value) {
                return "<span data-clipboard-text='{$value}' onclick='return false;' title='برای کپی کلیک کنید.' class='tooltips link-copy btn btn-default'>{$value}</span>";
            },
        ];
    }
    
    /**
     * Its a macro method for making bulk buttons
     *
     * @param string      $action
     * @param string      $modelClass
     * @param string|null $title
     * @param string|null $tooltips
     *
     * @return array
     */
    public static function bulk_delete($action, $modelClass, $title = null, $tooltips = null) {
        if ($title === null) {
            $title    = icon('fa-trash-o fa-lg');
            $tooltips = trans('messages.delete');
        }
        if ($tooltips === null) {
            $tooltips = trans('messages.delete');
        }
        return [
            'route'  => action($action, ['action' => 'delete']),
            'button' => function () use ($modelClass, $tooltips, $title) {
                if (Auth::user()->can('delete', new $modelClass())) {
                    return '<button type="submit" title="' . $tooltips . '" class="btn btn-sm btn-danger tooltips">' . $title . '</button>';
                }
            },
        ];
    }
    
    /**
     * Its a macro method for making bulk buttons
     *
     * @param string      $action
     * @param string      $modelClass
     * @param string|null $title
     * @param string|null $tooltips
     *
     * @return array
     */
    public static function bulk_change_status($action, $modelClass, $title = null, $tooltips = null) {
        if ($title === null) {
            $title    = icon('fa-star fa-lg');
            $tooltips = trans('messages.change status');
        }
        if ($tooltips === null) {
            $tooltips = trans('messages.change status');
        }
        return [
            'route'  => action($action, ['action' => 'status']),
            'button' => function () use ($modelClass, $title, $tooltips) {
                if (Auth::user()->can('update', new $modelClass())) {
                    return '<button type="submit" title="' . $tooltips . '" class="btn btn-sm btn-warning tooltips">' . $title . '</button>';
                }
            },
        ];
    }
    
    /**
     * Its a macro method for making bulk buttons
     *
     * @param string      $action
     * @param string      $modelClass
     * @param string|null $title
     * @param string|null $tooltips
     *
     * @return array
     */
    public static function bulk_change_order($action, $modelClass, $title = null, $tooltips = null) {
        if ($title === null) {
            $title    = icon('fa-refresh fa-lg');
            $tooltips = trans('messages.change sort order');
        }
        if ($tooltips === null) {
            $tooltips = trans('messages.change sort order');
        }
        return [
            'route'  => action($action, ['action' => 'order']),
            'button' => function () use ($modelClass, $title, $tooltips) {
                if (Auth::user()->can('update', new $modelClass())) {
                    return '<button type="submit" title="' . $tooltips . '" class="btn btn-sm btn-info tooltips">' . $title . '</button>';
                }
            },
        ];
    }
    
    /**
     * Its a macro method for making tools buttons
     *
     * @param string      $action
     * @param string|null $title
     * @param string|null $tooltips
     *
     * @return \Closure
     */
    public static function tool_edit($action, $title = null, $tooltips = null) {
        if ($title === null) {
            $title    = icon('fa-pencil-square-o');
            $tooltips = trans('messages.edit');
        }
        if ($tooltips === null) {
            $tooltips = trans('messages.edit');
        }
        return function (Model $record) use ($action, $title, $tooltips) {
            if (Auth::user()->can('update', $record)) {
                return '<a href="' . action($action, ['id' => $record->id]) . '" class="load btn btn-info tooltips" title="' . $tooltips . '" >' . $title . '</a>';
            }
        };
        
    }
    
    /**
     * Its a macro method for making tools buttons
     *
     * @param string      $action
     * @param string|null $title
     * @param string|null $tooltips
     *
     * @return \Closure
     */
    public static function tool_delete($action, $title = null, $tooltips = null) {
        if ($title === null) {
            $title    = icon('fa-trash-o');
            $tooltips = trans('messages.delete');
        }
        if ($tooltips === null) {
            $tooltips = trans('messages.delete');
        }
        return function (Model $record) use ($action, $title, $tooltips) {
            if (Auth::user()->can('delete', $record)) {
                return '<a href="' . action($action, ['id' => $record->id, '_method' => 'delete']) . '" class="ajax btn btn-danger tooltips" data-holder="#inWrapper" title="' . $tooltips . '" >' .
                       $title .
                       '</a>';
            }
        };
        
    }
}
