@extends('layouts.app')

@section('content')
<div class="col-md-12">
        <div class="card card-primary">
                <div class="card-header">
                  <h3 class="card-title">Welcome Message For Group</h3>
                </div>
                <!-- /.card-header -->
                <!-- form start -->
                <form role="form" method="POST" action="{{route('welcome.update')}}">
                  @csrf
                  <div class="card-body">
                    <div class="row" id="form-command">
                        <input type="hidden" name="command" value="{{@welcome}}" required>
                        <input type="hidden" name="description" value="Message for welcoming new user in Luno group" required>
                        <div class="form-group col-12">
                          <label for="message">Message</label>
                          <textarea rows="5" class="form-control" id="message" name="message" placeholder="Input Message" required>{{$cmd->message}}</textarea>
                          <p class="help-block">
                            <strong>You can use the word template below to show dynamic data (just put in anywhere in your message) :</strong>
                            <ul>
                              <li>
                                <b>@fname@ : </b> for showing first name of user
                              </li>
                              <li>
                                <b>@grouptitle@ : </b> for showing title of group (this is only work on group)
                              </li>
                              <li>
                                <b>@date@ : </b> for showing complete date ex: 'Thursday, 19 December 2019  01:27:49'
                              </li>
                            </ul>
                          </p>
                        </div>
                        @if (!empty(\json_decode($cmd->links)))
                          @foreach (\json_decode($cmd->links) as $item)
                            <div class="form-group col-6 link{{$loop->index}}">
                              <label for="exampleInputEmail1">Link</label>
                              <input type="text" name="link[]" class="form-control" placeholder="Input Link" value="{{$item}}">
                              <p class="help-block">
                                <strong>The url must start with "http://" or "https://"</strong>
                              </p>
                            </div>
                            @php
                                $title = json_decode($cmd->link_title);
                            @endphp
                            <div class="form-group col-3 link{{$loop->index}}">
                              <label for="exampleInputEmail1">Link Title</label>
                              <input type="text" name="link_title[]" class="form-control" placeholder="Input Link Title" value="{{$title[$loop->index]}}">
                            </div>
                            <div class="form-group col-3 link{{$loop->index}}">
                              <button type="button" id="remove" onclick="remove('link{{$loop->index}}');" class="btn btn-danger">Remove Link</button>
                            </div>
                          @endforeach
                          @else
                            <div class="form-group col-6">
                              <label for="exampleInputEmail1">Link</label>
                              <input type="text" name="link[]" class="form-control" placeholder="Input Link">
                              <p class="help-block">
                                <strong>The url must start with "http://" or "https://"</strong>
                              </p>
                            </div>
                            <div class="form-group col-3">
                              <label for="exampleInputEmail1">Link Title</label>
                              <input type="text" name="link_title[]" class="form-control" placeholder="Input Link Title">
                            </div>
                          @endif
                        <div class="form-group col-3">
                          <button type="button" id="add" class="btn btn-info">Add Link</button>
                        </div>
                    </div>
                  </div>
                  <!-- /.card-body -->
  
                  <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                  </div>
                </form>
              </div>
</div>
<script>
  CKEDITOR.replace('message',{
    toolbarGroups : [
      { name: 'document', groups: [ 'mode', 'document', 'doctools' ] },
      { name: 'editing', groups: [ 'find', 'selection', 'spellchecker', 'editing' ] },
      { name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
      { name: 'forms', groups: [ 'forms' ] },
      { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
      { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi', 'paragraph' ] },
      { name: 'links', groups: [ 'links' ] },
      { name: 'insert', groups: [ 'insert' ] },
      { name: 'styles', groups: [ 'styles' ] },
      { name: 'colors', groups: [ 'colors' ] },
      { name: 'tools', groups: [ 'tools' ] },
      { name: 'others', groups: [ 'others' ] },
      { name: 'about', groups: [ 'about' ] }
    ],

    removeButtons : 'Source,Save,NewPage,Preview,Print,Templates,Find,Replace,SelectAll,Scayt,Form,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField,Strike,Subscript,Superscript,Underline,RemoveFormat,CopyFormatting,NumberedList,Outdent,Blockquote,JustifyLeft,BidiLtr,BidiRtl,Language,JustifyRight,JustifyCenter,CreateDiv,Indent,BulletedList,JustifyBlock,Anchor,Unlink,Image,Flash,Table,HorizontalRule,Smiley,SpecialChar,PageBreak,Iframe,Format,Font,Styles,TextColor,BGColor,Maximize,ShowBlocks,FontSize',
    enterMode: CKEDITOR.ENTER_BR,
    shiftEnterMode: CKEDITOR.ENTER_BR,
    pasteFromWordPromptCleanup : true,
    pasteFromWordRemoveFontStyles : true,
    forcePasteAsPlainText : true,
    ignoreEmptyParagraph : true,
    removeFormatAttributes : true,
  });
    jQuery(document).ready(function () {
        var l = 1000;
        $('#add').click(function () {
          l++;
          var index = "link"+l;
          var a =   '<div class="form-group col-12 '+index+'">'+
                    '<div>'+
                    '<label for="exampleInputEmail1">Link</label>'+
                    '<input type="text" name="link[]" class="form-control" placeholder="Input Link">'+
                    '<p class="help-block">'+
                    '<strong>The url must start with "http://" or "https://"</strong>'+
                    '</p>'+
                    '</div>'+
                    '<div>'+
                    '<label for="exampleInputEmail1">Link Title</label>'+
                    '<input type="text" name="link_title[]" class="form-control" placeholder="Input Link Title">'+
                    '</div>'+
                    '<div class="'+index+'">'+
                    '<button type="button" id="remove" onclick="remove(\''+index+'\');" class="btn btn-danger">Remove Link</button>'+
                    '</div>'+
                    '</div>';
          $('#form-command').append(a);
        });
    })
    function remove(params) {
      $("."+params).remove();
    }
</script>
@endsection