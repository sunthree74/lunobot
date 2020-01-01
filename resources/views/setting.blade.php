@extends('layouts.app')

@section('content')
<div class="col-md-12">
        <div class="card card-primary">
                <div class="card-header">
                  <h3 class="card-title">Change Password</h3>
                </div>
                <!-- /.card-header -->
                <!-- form start -->
                <form role="form" method="POST" action="{{route('password.change')}}">
                    @csrf
                  <div class="card-body">
                    <div class="form-group">
                      <label for="exampleInputEmail1">Old Password</label>
                      <input type="password" name="old_password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1">New Password</label>
                        <input type="password" name="new_password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1">Password Confirmation</label>
                        <input type="password" name="confirm_password" class="form-control">
                    </div>
                  </div>
                  <!-- /.card-body -->
  
                  <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                  </div>
                </form>
              </div>
</div>
<div class="col-md-12">
  <div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title">Bot Filter</h3>
          </div>
          <!-- /.card-header -->
            <div class="card-body">
              <div class="form-group">
                {{-- <label for="exampleInputEmail1">Old Password</label> --}}
                <input type="checkbox" name="filter" checked data-bootstrap-switch data-off-color="danger" data-on-color="success">
              </div>
            </div>
            <!-- /.card-body -->
        </div>
</div>
<script>
  $(document).ready(function () {
    var filter = $('input[name="filter"]');
    filter.bootstrapSwitch('state', true, true);
    @if($v->value == 'false')
      filter.bootstrapSwitch('state', false, true);
    @endif
    filter.on('switchChange.bootstrapSwitch', function(event, state) {
      console.log(state.toString());
      
      $.ajax({
        url: "{{ url('toggleswitch') }}"+ '/' + state.toString(),
        type: "GET",
        success : function(a){
          if (a.message == 'true') {
            swal("Yeeahh!", "Bot filter is turn on", "success");
          }else{
            swal("Yeeahh!", "Bot filter is turn off", "success");
          }
        },
        error : function () {
          swal("Error!", "OOuuh the system ill.", "error");
        }
      })
    });
  })
</script>
@endsection