@extends('layouts.app')

@section('content')
<div class="col-md-12">
        <div class="card card-default">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fas fa-bullhorn"></i>
              Telegram Command
            </h3>
            <div class="card-tools">
                <ul class="nav nav-pills ml-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="{{route('command.create')}}" title="New Command"><i class="nav-icon fa fa-plus"></i></a>
                    </li>
                </ul>
            </div>
          </div>
          <!-- /.card-header -->
          <div class="card-body">
              @foreach ($cmds as $cmd)
              @if ($cmd->command != '@welcome')
              <div class="callout callout-info">
                <a href="javascript:void(0);" class="btn close" title="Remove Command" onclick="removeCommand({{$cmd->id}})"><i class="nav-icon fa fa-trash"></i></a>
                  <a class="btn close" href="{{route('command.edit', $cmd->id)}}" title="Edit Command"><i class="nav-icon fa fa-edit"></i></a>
                  <h5><b>{{$cmd->command}}</b></h5>
                  <p>{!!$cmd->message!!}</p>
                  @if (!is_null($cmd->links[0]))
                  <p>Button Link</p>
                      <ul>
                        @foreach (\json_decode($cmd->links) as $item)
                          @php
                            $title = json_decode($cmd->link_title);
                          @endphp
                          <li><a href="{{$item}}">{{$title[$loop->index]}}</a></li>
                        @endforeach
                      </ul>
                  @endif
              </div>
              @endif
                
              @endforeach

              {{$cmds->appends(Request::all())->links()}}
          </div>
          <!-- /.card-body -->
        </div>
        <!-- /.card -->
</div>
<script>
  function removeCommand(params) {
    var csrf_token = $('meta[name="csrf-token"]').attr("content");
    swal({
          title: 'Are You Sure?',
          text: "The deleted command can't be restored",
          type: 'warning',
          showCancelButton: true,
          confirmButtonClass: "red-thunderbird",
          cancelButtonClass: 'blue',
          confirmButtonText: 'Yes, I\'m Sure',
          cancelButtonText: 'No, cancel!',
        },
				function(isConfirm){
				  if (isConfirm){
            $.ajax({
              url: "{{ url('command') }}"+ '/' + params,
              type: "POST",
              data : {'_method' : 'DELETE', '_token' : csrf_token},
              success : function(){
                swal("Deleted!", "Command Successfully Deleted.", "success");
                location.reload();
              },
              error : function () {
                swal("Error!", "OOuuh the system ill.", "error");
              }
            })
				  }
        }
    );
  }
</script>
@endsection