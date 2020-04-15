<?php
    $url = $_POST['url'] ?? '';
    $keywords = $_POST['keywords'] ?? '';
    $matchWholeWords = $_POST['wholeWords'] !== '0';

    $url = trim($url);
    $keywords = trim($keywords);
    $keywordsArray = [];
    $text = false;

    $keywordOccurrences = [];
    if ($url !== '')
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $text = curl_exec($ch);

        if ($text !== false && $keywords !== '')
        {
            $keywordsArray = explode(' ', $keywords);

            foreach ($keywordsArray as $keyword)
            {
                if ($keyword === '')
                    continue;

                $keywordOccurrences[$keyword] = 0;
            }

            if ($matchWholeWords)
            {
                $explodedText = explode(' ', $text);

                $replaces = [];
                for ($i = 0; $i < count($explodedText); $i++)
                {
                    $word = $explodedText[$i];
                    foreach ($keywordsArray as $keyword)
                    {
                        if (strtolower($word) === strtolower($keyword))
                        {
                            $keywordOccurrences[$keyword]++;
                            $replaceCount = $replaces[$keyword] = ($replaces[$keyword] !== null) ? $replaces[$keyword] + 1 : 1;
                            $explodedText[$i] = "<span id=\"$keyword-$replaceCount\" class=\"keyword\">$word</span>";
                        }
                    }
                }

                $text = implode(' ', $explodedText);
            }
            else
            {
                $occurrenceData = [];
                $replaceCounts = [];
                foreach ($keywordsArray as $keyword)
                {
                    $replaceCounts[$keyword] = 0;
                    if ($keyword !== '')
                    {
                        $lastPos = 0;
                        while (($lastPos = strpos(strtolower($text), strtolower($keyword), $lastPos)) !== false)
                        {
                            $keywordOccurrences[$keyword]++;
                            $occurrenceData[] = ['startPos' => $lastPos, 'keyword' => $keyword];
                            $lastPos += strlen($keyword);
                        }
                    }
                }

                $cmp = function ($a, $b)
                {
                    return ($a['startPos'] < $b['startPos']) ? -1 : 1;
                };

                usort($occurrenceData, $cmp);

                $offset = 0;
                for ($i = 0; $i < count($occurrenceData); $i++)
                {
                    $occurrenceDatum = $occurrenceData[$i];
                    $startPos = $occurrenceDatum['startPos'] + $offset;
                    $keyword = $occurrenceDatum['keyword'];
                    $replaceCounts[$keyword]++;
                    $length = strlen($keyword);
                    $word = substr($text, $startPos, $length);
                    $openTag = "<span id=\"$keyword-{$replaceCounts[$keyword]}\" class=\"keyword\">";
                    $closeTag = "</span>";
                    $text = substr($text, 0, $startPos) . $openTag . $word . $closeTag . substr($text, $startPos + $length);
                    $offset += strlen($openTag) + strlen($closeTag);
                }
            }
        }
        curl_close($ch);
    }
?>
<!doctype html>
<html lang="en">
<head>
    <title>Text Manager</title>
    <link rel="stylesheet" href="https://unpkg.com/mustard-ui@latest/dist/css/mustard-ui.min.css">
    <style>
        .keyword
        {
            color: white;
            background-color: #4caf50;
        }
    </style>
</head>
<body>
    <header style="height: 250px">
        <h1>Text Manager</h1>
    </header>
    <main class="row">
        <section class="col-sm-6 panel" id="searchPanel">
            <section>
                <form method="post">
                    <h2 class="panel-title">1. Fill Form</h2>
                    <fieldset>
                        <label class="panel-title">Insert Text URL</label>
                        <input type="text" name="url" value="<?= $url ?>">
                        <label class="panel-title">Insert keywords to find, space separated</label>
                        <input type="text" name="keywords" value="<?= $keywords ?>"><br>
                        <input type="hidden" name="wholeWords" value="0">
                        <label class="panel-title"><input type="checkbox" name="wholeWords" value="1" <?= $matchWholeWords ? 'checked' : '' ?>>Match whole words</label><br>
                        <button class="button-primary" style="margin-top: 1.5em">Send</button>
                    </fieldset>
                </form>
            </section>
            <section>
                <h2 class="panel-title">2. Check Keywords</h2>
                <div class="stepper">
                    <?php foreach ($keywordsArray as $keyword): ?>
                        <div class="step">
                            <p class="step-number"><?= $keywordOccurrences[$keyword] ?></p>
                            <p class="step-title"><?= $keyword ?></p>
                            <?php for ($i = 0; $i < $keywordOccurrences[$keyword]; $i++): ?>
                            <a href="#<?=$keyword . '-' . ($i + 1)?>"><?= ($i + 1) ?></a>
                            <?php endfor; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </section>
        <section class="col-sm-6" id="textArea">
            <?php if ($text !== false): ?>
                <pre><code><?= $text ?></code></pre>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
