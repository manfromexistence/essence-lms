@php
    $elements = $template->layout_config ?? [];
    if (empty($elements)) {
        $elements = [
            ['type' => 'text', 'content' => '{institution_name}', 'x' => 50, 'y' => 40, 'width' => 700, 'fontSize' => 44, 'fontFamily' => 'Georgia, serif', 'color' => '#166534', 'bold' => true, 'align' => 'center'],
            ['type' => 'text', 'content' => 'CERTIFICATE OF COMPLETION', 'x' => 50, 'y' => 130, 'width' => 700, 'fontSize' => 22, 'fontFamily' => 'Arial, sans-serif', 'color' => '#166534', 'bold' => true, 'letterSpacing' => 6, 'align' => 'center'],
            ['type' => 'text', 'content' => 'This certifies that', 'x' => 50, 'y' => 210, 'width' => 700, 'fontSize' => 20, 'fontFamily' => 'Georgia, serif', 'color' => '#374151', 'align' => 'center'],
            ['type' => 'text', 'content' => '{student_name}', 'x' => 50, 'y' => 260, 'width' => 700, 'fontSize' => 52, 'fontFamily' => 'Georgia, serif', 'color' => '#111827', 'bold' => true, 'italic' => true, 'align' => 'center'],
            ['type' => 'text', 'content' => 'has successfully completed all learning requirements for', 'x' => 50, 'y' => 350, 'width' => 700, 'fontSize' => 18, 'fontFamily' => 'Georgia, serif', 'color' => '#374151', 'align' => 'center'],
            ['type' => 'text', 'content' => '{course_name}', 'x' => 50, 'y' => 400, 'width' => 700, 'fontSize' => 34, 'fontFamily' => 'Georgia, serif', 'color' => '#166534', 'bold' => true, 'align' => 'center'],
            ['type' => 'text', 'content' => 'Grade: {grade}', 'x' => 50, 'y' => 470, 'width' => 700, 'fontSize' => 18, 'fontFamily' => 'Arial, sans-serif', 'color' => '#374151', 'align' => 'center'],
            ['type' => 'text', 'content' => 'Certificate No: {certificate_number}  |  Date: {issued_at}', 'x' => 50, 'y' => 640, 'width' => 700, 'fontSize' => 13, 'fontFamily' => 'Arial, sans-serif', 'color' => '#6b7280', 'align' => 'center'],
            ['type' => 'text', 'content' => 'Verification: {verification_code}', 'x' => 50, 'y' => 665, 'width' => 700, 'fontSize' => 12, 'fontFamily' => 'Arial, sans-serif', 'color' => '#9ca3af', 'align' => 'center'],
        ];
    }

    // Sample data for previews
    $sample = [
        'institution_name' => $sampleName ?? 'Dhaka IT Institute',
        'student_name' => $sampleStudent ?? 'Rafiqul Islam',
        'course_name' => $sampleCourse ?? 'Full Stack Web Development',
        'certificate_number' => $sampleNumber ?? 'DII-202608-00001-001',
        'verification_code' => $sampleCode ?? 'ABC123XYZ789',
        'issued_at' => $sampleDate ?? '07 Aug 2026',
        'grade' => $sampleGrade ?? 'A+',
    ];

    $defaults = [
        'type' => 'text', 'content' => '', 'x' => 50, 'y' => 50, 'width' => 700,
        'fontSize' => 20, 'fontFamily' => 'Georgia, serif', 'color' => '#111827',
        'bold' => false, 'italic' => false, 'underline' => false, 'align' => 'center',
        'letterSpacing' => 0, 'opacity' => 1, 'imageField' => 'logo', 'height' => 60,
    ];

    $fontMap = [
        'serif' => 'Georgia, "Times New Roman", serif',
        'sans' => 'Arial, Helvetica, sans-serif',
        'mono' => 'Courier New, monospace',
        'cursive' => '"Brush Script MT", cursive',
        'Georgia, serif' => 'Georgia, "Times New Roman", serif',
        'Arial, sans-serif' => 'Arial, Helvetica, sans-serif',
        'Courier New, monospace' => 'Courier New, monospace',
        '"Brush Script MT", cursive' => '"Brush Script MT", cursive',
    ];
@endphp

@foreach($elements as $el)
    @php
        $el = array_merge($defaults, $el);
        $style = '';
        $style .= 'position:absolute;';
        $style .= 'left:' . ($el['x'] ?? 50) . 'px;';
        $style .= 'top:' . ($el['y'] ?? 50) . 'px;';
        if ($el['type'] === 'text') {
            $style .= 'width:' . ($el['width'] ?? 700) . 'px;';
            $style .= 'font-size:' . ($el['fontSize'] ?? 20) . 'px;';
            $style .= 'font-family:' . ($fontMap[$el['fontFamily']] ?? $el['fontFamily'] ?? 'Georgia, serif') . ';';
            $style .= 'color:' . ($el['color'] ?? '#111827') . ';';
            $style .= 'text-align:' . ($el['align'] ?? 'center') . ';';
            if (!empty($el['bold'])) $style .= 'font-weight:bold;';
            if (!empty($el['italic'])) $style .= 'font-style:italic;';
            if (!empty($el['underline'])) $style .= 'text-decoration:underline;';
            if (!empty($el['letterSpacing'])) $style .= 'letter-spacing:' . $el['letterSpacing'] . 'px;';
            if (isset($el['opacity'])) $style .= 'opacity:' . $el['opacity'] . ';';
            $content = $el['content'] ?? '';
            // Replace variables
            foreach ($sample as $key => $value) {
                $content = str_replace('{' . $key . '}', $value, $content);
            }
            echo "<div style=\"{$style}\">" . e($content) . '</div>';
        } else {
            // Image element
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
                echo "<img src=\"{$imgUrl}\" style=\"{$style}\">";
            }
        }
    @endphp
@endforeach
