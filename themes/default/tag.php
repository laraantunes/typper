<main>
    <header style="margin-bottom: 40px; border-bottom: 2px solid #007bff; display: inline-block; padding-bottom: 5px;">
        <h2 style="margin: 0; color: #007bff;">🏷️ Tag: #<?= htmlspecialchars($tag) ?></h2>
    </header>

    <div class="tag-list">
        <?php 
        $contents = get_contents_by_tag($tag);
        // Ordenar do mais recente para o mais antigo
        usort($contents, function($a, $b) {
            return strtotime($b->creationDate) - strtotime($a->creationDate);
        });
        
        if (empty($contents)): 
        ?>
            <p>Nenhum conteúdo encontrado com esta tag.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <?php foreach ($contents as $item): ?>
                    <article style="padding: 20px; background: #fff; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <h3 style="margin: 0 0 10px 0; font-size: 1.5em;">
                            <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?: '/' ?>/<?= htmlspecialchars($item->slug) ?>" style="color: #222; text-decoration: none;">
                                <?= htmlspecialchars($item->title ?? 'Sem título') ?>
                            </a>
                        </h3>
                        <div class="meta-info" style="margin-bottom: 15px;">
                            <span>📅 <?= date('d/m/Y', strtotime($item->creationDate)) ?></span>
                            <?php if (!empty($item->author)): ?>
                                <span style="margin-left: 10px;">✍️ <?= htmlspecialchars($item->author) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($item->subtitle)): ?>
                            <p style="color: #555; margin: 0;"><?= htmlspecialchars($item->subtitle) ?></p>
                        <?php endif; ?>
                        
                        <div style="margin-top: 15px;">
                            <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?: '/' ?>/<?= htmlspecialchars($item->slug) ?>" style="color: #007bff; text-decoration: none; font-weight: bold;">Ler mais &rarr;</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
