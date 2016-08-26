<div class="form-group {{ $errors->has($options['validateName']) ? 'has-error' : '' }}">
    {!! Form::label($fieldName, $options['label']) !!}
    {!! Form::select($fieldName, $options['select_options'], $record->{$options['relationMethod']}->pluck($options['selectedKey'])->all(), [
        	'class'=>'form-control select2 '.array_get($options, "additionalClass"), 'multiple'=>'multiple', 'style'=>'width: 100%;'
        ])
    !!}
    <div class="text-red">
        {{ join($errors->get($options['validateName']), '<br />') }}
    </div>
</div>
