<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Enviando csv</title>
</head>

<body>
    @if (session('success'))
        <p>{!! session('success') !!}</p>
    @endif

    @if (session('error'))
    <p>{!! session('error') !!}</p>
    @endif

    @if ($errors->any())
        @foreach ( $errors->all() as $error )
        <p>{{ $error }}</p>
        @endforeach
    @endif

    <form action="{{ route('user.import') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <input type="file" name="file" id="file" accept=".csv, .txt">
        <button type="submit" id="file-btn">Importar</button>

    </form>

    @foreach ( $users as $user)
        {{ $user -> id }}

    @endforeach


</body>

</html>
