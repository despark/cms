<img {!! $attributes !!} src="{{ asset($image->getOriginalImagePath($thumb)) }}" alt="{{ $image->alt }}" title="{{$image->title}}"
       srcset="{{ asset($image->getOriginalImagePath($thumb)) }} 1x, {{ asset($image->getRetinaImagePath($thumb)) }} {{$image->retina_factor}}x">
