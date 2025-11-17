<?php

/**
 * Loads an HTML template file and replaces placeholders with provided data.
 *
 * @param string $templatePath The full path to the HTML template file.
 * @param array $data An associative array where keys are placeholder names (without {{}})
 *                    and values are the data to replace them with.
 * @return string The processed HTML content with placeholders replaced.
 * @throws Exception If the template file cannot be read.
 */
function loadTemplate(string $templatePath, array $data): string
{
    if (!file_exists($templatePath)) {
        throw new Exception("Template file not found: {$templatePath}");
    }

    $templateContent = file_get_contents($templatePath);
    if ($templateContent === false) {
        throw new Exception("Failed to read template file: {$templatePath}");
    }

    foreach ($data as $key => $value) {
        $templateContent = str_replace("{{{$key}}}", (string)$value, $templateContent);
    }

    return $templateContent;
}
