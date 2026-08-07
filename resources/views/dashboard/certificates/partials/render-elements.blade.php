@php
    $layout = $template->layout_config ?? [];
    $elements = $layout['elements'] ?? (is_array($layout) && isset($layout[0]) ? $layout : []);
    $bgOpacity = $layout['background_opacity'] ?? 0.6;

    if (empty($elements)) {
        $elements = [
            ['type' => 'text', 'content' => '{institution_name}', 'x' => 50, 'y' => 110, 'width' => 1100, 'fontSize' => 46, 'fontFamily' => 'Georgia, serif', 'color' => '#14532d', 'bold' => true, 'align' => 'center', 'opacity' => 1],
            ['type' => 'text', 'content' => 'CERTIFICATE OF COMPLETION', 'x' => 50, 'y' => 190, 'width' => 1100, 'fontSize' => 24, 'fontFamily' => 'Arial, sans-serif', 'color' => '#166534', 'bold' => true, 'letterSpacing' => 8, 'align' => 'center', 'opacity' => 1],
            ['type' => 'text', 'content' => 'This certifies that', 'x' => 50, 'y' => 290, 'width' => 1100, 'fontSize' => 22, 'fontFamily' => 'Georgia, serif', 'color' => '#374151', 'align' => 'center', 'opacity' => 1],
            ['type' => 'text', 'content' => '{student_name}', 'x' => 50, 'y' => 345, 'width' => 1100, 'fontSize' => 60, 'fontFamily' => 'Georgia, serif', 'color' => '#111827', 'bold' => true, 'italic' => true, 'align' => 'center', 'opacity' => 1],
            ['type' => 'text', 'content' => 'has successfully completed all learning requirements for', 'x' => 50, 'y' => 450, 'width' => 1100, 'fontSize' => 20, 'fontFamily' => 'Georgia, serif', 'color' => '#374151', 'align' => 'center', 'opacity' => 1],
            ['type' => 'text', 'content' => '{course_name}', 'x' => 50, 'y' => 505, 'width' => 1100, 'fontSize' => 40, 'fontFamily' => 'Georgia, serif', 'color' => '#166534', 'bold' => true, 'align' => 'center', 'opacity' => 1],
            ['type' => 'text', 'content' => 'Grade: {grade}', 'x' => 50, 'y' => 585, 'width' => 1100, 'fontSize' => 20, 'fontFamily' => 'Arial, sans-serif', 'color' => '#374151', 'align' => 'center', 'opacity' => 1],
            ['type' => 'image', 'imageField' => 'signature', 'x' => 520, 'y' => 660, 'width' => 170, 'height' => 70, 'opacity' => 1],
            ['type' => 'text', 'content' => 'Certificate No: {certificate_number}  |  Issued: {issued_at}', 'x' => 50, 'y' => 790, 'width' => 1100, 'fontSize' => 14, 'fontFamily' => 'Arial, sans-serif', 'color' => '#6b7280', 'align' => 'center', 'opacity' => 1],
            ['type' => 'text', 'content' => 'Verify: {verification_code}', 'x' => 50, 'y' => 820, 'width' => 1100, 'fontSize' => 12, 'fontFamily' => 'Arial, sans-serif', 'color' => '#9ca3af', 'align' => 'center', 'opacity' => 1],
        ];
    }

    // Student detail values — pass real values from the caller or use samples
    $values = [
        'institution_name' => $values['institution_name'] ?? 'Dhaka IT Institute',
        'student_name' => $values['student_name'] ?? 'Rafiqul Islam',
        'course_name' => $values['course_name'] ?? 'Full Stack Web Development',
        'certificate_number' => $values['certificate_number'] ?? 'DII-202608-00001-001',
        'verification_code' => $values['verification_code'] ?? 'ABC123XYZ789',
        'issued_at' => $values['issued_at'] ?? '07 Aug 2026',
        'grade' => $values['grade'] ?? 'A+',
        'student_id' => $values['student_id'] ?? 'STU-0001',
        'student_phone' => $values['student_phone'] ?? '01712345678',
        'student_email' => $values['student_email'] ?? 'student@example.com',
        'course_code' => $values['course_code'] ?? 'DIT-WD-01',
        'course_duration' => $values['course_duration'] ?? '12 months',
        'institution_phone' => $values['institution_phone'] ?? '+880 1682-71557',
        'institution_address' => $values['institution_address'] ?? 'Mirpur-10, Dhaka',
    ];

    $fontMap = [
        'Georgia, serif' => 'Georgia, "Times New Roman", serif',
        'Arial, sans-serif' => 'Arial, Helvetica, sans-serif',
        'Courier New, monospace' => '"Courier New", monospace',
        '"Brush Script MT", cursive' => '"Brush Script MT", cursive',
        'serif' => 'Georgia, "Times New Roman", serif',
        'sans' => 'Arial, Helvetica, sans-serif',
        'mono' => '"Courier New", monospace',
        'cursive' => '"Brush Script MT", cursive',
    ];

    $defaults = [
        'type' => 'text', 'content' => '', 'x' => 50, 'y' => 50, 'width' => 700,
        'fontSize' => 20, 'fontFamily' => 'Georgia, serif', 'color' => '#111827',
        'bold' => false, 'italic' => false, 'underline' => false, 'align' => 'center',
        'letterSpacing' => 0, 'opacity' => 1, 'imageField' => 'logo', 'height' => 60,
        'rotation' => 0,
    ];
@endphp

@foreach($elements as $el)
    @php
        $el = array_merge($defaults, is_array($el) ? $el : []);
        $style = 'position:absolute;left:' . ($el['x'] ?? 50) . 'px;top:' . ($el['y'] ?? 50) . 'px;';
        $rotation = $el['rotation'] ?? 0;
        if ($rotation) $style .= 'transform:rotate(' . $rotation . 'deg);transform-origin:center;';
        if (isset($el['opacity'])) $style .= 'opacity:' . $el['opacity'] . ';';
        $dataAttrs = 'data-elem-type="' . e($el['type']) . '" data-elem-index="' . $loop->index . '"';
    @endphp
    @if($el['type'] === 'text')
        @php
            $style .= 'width:' . ($el['width'] ?? 700) . 'px;';
            $style .= 'font-size:' . ($el['fontSize'] ?? 20) . 'px;';
            $style .= 'font-family:' . ($fontMap[$el['fontFamily']] ?? $el['fontFamily'] ?? 'Georgia, serif') . ';';
            $style .= 'color:' . ($el['color'] ?? '#111827') . ';';
            $style .= 'text-align:' . ($el['align'] ?? 'center') . ';';
            if (!empty($el['bold'])) $style .= 'font-weight:bold;';
            if (!empty($el['italic'])) $style .= 'font-style:italic;';
            if (!empty($el['underline'])) $style .= 'text-decoration:underline;';
            if (!empty($el['letterSpacing'])) $style .= 'letter-spacing:' . $el['letterSpacing'] . 'px;';
            $content = $el['content'] ?? '';
            foreach ($values as $key => $value) {
                $content = str_replace('{' . $key . '}', $value, $content);
            }
        @endphp
        <div {!! $dataAttrs !!} style="{!! $style !!}" class="cert-elem">{{ $content }}</div>
    @else
        @php
            $imgUrl = '';
            if (($el['imageField'] ?? '') === 'logo') {
                $imgUrl = $template->logo_image ? asset('storage/' . $template->logo_image) : app(\App\Services\SettingsService::class)->getLogo();
            } elseif (($el['imageField'] ?? '') === 'signature') {
                $imgUrl = $template->signature_image ? asset('storage/' . $template->signature_image) : '';
            } elseif (($el['imageField'] ?? '') === 'background') {
                $imgUrl = $template->background_image ? asset('storage/' . $template->background_image) : '';
            } elseif (($el['imageField'] ?? '') === 'custom' && !empty($el['imageUrl'])) {
                $imgUrl = $el['imageUrl'];
            }
            if ($imgUrl) {
                $style .= 'width:' . ($el['width'] ?? 160) . 'px;';
                $style .= 'height:' . ($el['height'] ?? 60) . 'px;';
                $style .= 'object-fit:contain;';
            }
        @endphp
        @if($imgUrl)
            <img {!! $dataAttrs !!} src="{{ $imgUrl }}" style="{!! $style !!}" class="cert-elem">
        @endif
    @endif
@endforeach
