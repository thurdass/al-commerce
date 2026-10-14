@if (session('status'))
    <div class="alert alert-success" role="status"><p>{{ session('status') }}</p></div>
@endif
@if ($errors->any())
    <div class="alert alert-error" role="alert" tabindex="-1">
        <p><strong>Confira os dados informados:</strong></p>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
