<div id="{{$grid['topDiv']}}">
	<div class="panel panel-default">
		<table id="{{$grid['topTable']}}" class="table table-hover table-grid">
			<thead>
				<tr class="grid-head">
					@foreach($grid['options'] as $option)
						<th @if($option['width'] > 0) width="{{$option['width']}}" @endif >
							<div class="th-sort">
								<form autocomplete="off" method="get" class="clearfix th-form {{$grid['formName']}} notAjax" action="{{$grid['url']}}">
									<p>{!! $option['title'] !!}</p>
									<div class="form-group">
										{!! $option['form'] !!}
									</div>
								</form>
								@if($option['sort'])
									<a class="fa {{$option['up']}} gridLoad tooltips" title="@lang('grid.ascending')" href="{{$option['upLink']}}"></a>
									<a class="fa {{$option['down']}} gridLoad tooltips" title="@lang('grid.descending')" href="{{$option['downLink']}}"></a>
								@else
									<p class="height-justified"></p>
								@endif
							</div>
						</th>
					@endforeach
					@if(!empty($grid['tools']))
						<th>
							<div class="th-sort">
								<form autocomplete="off" method="get" class="clearfix th-form {{$grid['formName']}} notAjax" action="{{$grid['url']}}">
									<p>@lang('grid.tools')</p>
									<div class="form-group">
										<form method="get" class="{{$grid['formName']}} notAjax" action="{{$grid['url']}}" autocomplete="off">
											<button class="btn btn-default tooltips" type="submit" title="@lang('grid.search')">
												<i class="fa fa-search"></i>
											</button>
										</form>
										<a class="btn btn-default gridLoad tooltips" href="{{$grid['url']}}" title="@lang('grid.reset')"><i class="fa fa-refresh"></i></a>
									</div>
								</form>
								<p class="countSpan">{{$grid['dataCount']}} @lang('grid.result')</p>
							</div>
						</th>
					@endif
				</tr>
			</thead>
			<tbody>
				@if(!empty($grid['body']))
					@foreach($grid['body'] as $k => $tr)
						<tr>
							@foreach ($tr as $td)
								<td dir="auto">{!! $td !!}</td>
							@endforeach
							@if(!empty($grid['tools']))
								<td>{!! $grid['extra'][$k]['tools'] !!}</td>
							@endif
						</tr>
					@endforeach
				@else
					<tr>
						<td colspan="{{$grid['countColumns']}}">@lang('grid.no result found')</td>
					</tr>
				@endif
			</tbody>
			<tfoot>
				<tr>
					<th colspan="{{$grid['countColumns']}}">
						@if($grid['bulks'])
							@foreach($grid['bulks'] as $bulk)
								<form method="post" class="{{$grid['formName']}}Bulk notAjax bulkForm" action="{{$bulk['route']}}" autocomplete="off">
									{!! $bulk['form'] !!}
								</form>
							@endforeach
						@endif
						
						@if($grid['pagination'])
							<div class="form-group pull-right">
								<ul class="pagination pagination-md" style="margin: 0;">
									@foreach($grid['pagination'] as $paginate)
										<li class="{{$paginate['class']}}">
											<a @if($paginate['class'] != 'disabled') class="gridLoad" href="{{$paginate['value']}}" @else href="{{$paginate['value']}}" @endif >{!! $paginate['key'] !!}</a>
										</li>
									@endforeach
								</ul>
								<span class="gridPage"> @lang('grid.page')
									<form method="get" class="{{$grid['formName']}} notAjax" autocomplete="off" action="{{$grid['url']}}">
		                                <input type="number" class="form-control" name="page" value="{{$grid['page']}}">
	                                </form>@lang('grid.from') {{$grid['pagesCount']}}
	                            </span>
							</div>
						@endif
						
						@if($grid['pageSize'])
							<form method="get" class="{{$grid['formName']}} notAjax grid-sizer form-inline" action="{{$grid['url']}}" autocomplete="off">
								<div class="form-group">
									<label class="control-label">@lang('grid.show count')</label>
									<select name="size" class="form-control">
										@foreach($grid['pageSize'] as $page)
											<option value="{{$page['value']}}" @if($page['selected']) selected="selected" @endif >{{$page['key']}}</option>
										@endforeach
									</select>
								</div>
							</form>
						@endif
						
						<form method="get" class="{{$grid['formName']}} notAjax hidden" action="{{$grid['url']}}" autocomplete="off">
							@if(empty($grid['pagination']))
								<input type="hidden" name="page" value="{{$grid['page']}}" />
							@endif
							<input type="hidden" name="{{$grid['prefix']}}sort" value="{{$grid['sort']}}" />
							<input type="hidden" name="{{$grid['prefix']}}mode" value="{{$grid['mode']}}" />
						</form>
					</th>
				</tr>
			</tfoot>
		</table>
	</div>
</div>