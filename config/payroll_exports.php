<?php

return [
    /**
     * Optional absolute path to an I&M Bank salary upload template.
     * If unset/missing, the exporter generates a compatible XLS file.
     */
    'imbank_template_path' => env('IMBANK_SALARY_UPLOAD_TEMPLATE_PATH'),
];

