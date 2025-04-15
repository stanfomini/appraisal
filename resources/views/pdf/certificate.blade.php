<!DOCTYPE html>
<html>
<head>
    <title>Appraisal Certificate</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; }
        .certificate { border: 2px solid #000; padding: 20px; margin: 20px; }
        .header { font-size: 24px; font-weight: bold; margin-bottom: 20px; }
        .details { font-size: 16px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="header">Appraisal Certificate</div>
        <div class="details">
            Vehicle: {{ $formResponse->year }} {{ $formResponse->make }} {{ $formResponse->model }}
        </div>
        <div class="details">VIN: {{ $formResponse->vin }}</div>
        <div class="details">Appraised Value: ${{ number_format($formResponse->appraisal_value, 2) }}</div>
        <div class="details">
            Valid Until: 
            {{ $certificate->expiry ? $certificate->expiry->format('Y-m-d') : 'N/A' }}
        </div>
        <div class="details">
            Appraised By: {{ optional($formResponse->appraiser)->name ?? 'N/A' }}
        </div>
    </div>

    <!-- Download Button -->
    <div style="margin: 20px;">
        <a 
            href="{{ route('certificates.download', ['tenant' => tenant('id'), 'certificateId' => $certificate->id]) }}"
            style="display:inline-block; padding: 10px 20px; background: #333; color: #fff; text-decoration: none; border-radius: 4px;"
        >
            Download PDF
        </a>
    </div>
</body>
</html>

