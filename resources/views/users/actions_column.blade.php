<a class="btn btn-sm btn-primary" href="/{{$path}}/{{$id}}/edit">{{ __('Edit') }}</a>
<form action="/{{$path}}/{{$id}}" method="POST" style="display:inline">
    @csrf
    @method('DELETE')
    <button type="button" class="btn btn-sm btn-danger" onclick="confirm('{{ __("Are you sure you want to delete this?") }}') ? this.parentElement.submit() : ''">
        {{ __('Delete') }}
    </button>
</form>