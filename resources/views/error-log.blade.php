<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <title>Hello, world!</title>
  </head>
  <body>


  <div class="container">
    <div class="row mt-5">
        <table class="table table-responsive">
            <thead>
                <th>id</th>
                <th>date</th>
                <th>TEST</th>
<th>api_url</th>
<th>user</th>
<th>user_id</th>
<th>message</th>
<th>status_code</th>
<th>line</th>
<th>file</th>
<th>fields</th>

<th>ip_address</th>
<th>request_method</th>
            </thead>
            <tbody>
                @foreach ($error_logs as $error_log)
                <tr>
                    <td>{{$error_log->id}} </td>
                    <td>{{$error_log->created_at}} </td>
                    <td><a class="btn btn-primary"  href="{{route('api-call',$error_log->id)}}" target="_blank">TEST</a> </td>
                    <td>{{$error_log->api_url}} </td>

<td> @if ($error_log->ERRuser)
    {{ ($error_log->ERRuser->first_Name ." ". $error_log->ERRuser->last_Name ." ". $error_log->ERRuser->last_Name )}}
@endif </td>
<td>{{$error_log->user_id}} </td>
<td>{{$error_log->message}} </td>
<td>{{$error_log->status_code}} </td>
<td>{{$error_log->line}} </td>
<td>{{$error_log->file}} </td>
<td>{{$error_log->fields}} </td>

<td>{{$error_log->ip_address}} </td>
<td>{{$error_log->request_method}} </td>
                </tr>
                @endforeach


            </tbody>

        </table>

        {{$error_logs->links()}}

    </div>
  </div>

    <!-- Optional JavaScript; choose one of the two! -->

    <!-- Option 1: Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

    <!-- Option 2: Separate Popper and Bootstrap JS -->
    <!--
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
    -->
  </body>
</html>
