<main class="content-body">
    <article>
        <header style="margin-bottom: 30px;">
            <h1 style="margin-bottom: 5px; font-size: 2.2em;"><?= htmlspecialchars($content->title ?? 'Sem Título') ?></h1>
            <div class="meta-info">
                <?php if (!empty($content->creationDate)): ?>
                    <span>📅 Publicado em <?= date('d/m/Y', strtotime($content->creationDate)) ?></span>
                <?php endif; ?>
                
                <?php if (!empty($content->author)): ?>
                    <span> ✍️ por <?= htmlspecialchars($content->author) ?></span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($content->tags)): ?>
                <div style="margin-top: 15px;">
                    <?php 
                    $tags = is_array($content->tags) ? $content->tags : explode(',', (string)$content->tags);
                    foreach ($tags as $tag): 
                        if (trim($tag)):
                            $cleanTag = trim($tag);
                    ?>
                        <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?: '/' ?>/tag/<?= urlencode($cleanTag) ?>" class="tag" style="text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='#cce5ff'" onmouseout="this.style.background='#eef'">#<?= htmlspecialchars($cleanTag) ?></a>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            <?php endif; ?>
        </header>

        <div class="article-content">
            <?= $content->content ?>
        </div>
    </article>
</main>