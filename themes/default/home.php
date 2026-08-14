<?php include_once __DIR__ . '/header.php'; ?>

<main>
    <section style="margin-bottom: 40px; text-align: center; padding: 40px 20px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <h2 style="font-size: 2.5em; margin-bottom: 10px; color: #007bff;">Bem-vindo!</h2>
        <p style="font-size: 1.2em; color: #555;"><?= htmlspecialchars(config('description') ?: 'Esta é a página inicial do tema padrão do Typper CMS.') ?></p>
    </section>

    <div style="margin-bottom: 40px; padding: 20px; background: #eef; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h3 style="margin-top: 0; color: #0056b3; display: flex; align-items: center; gap: 8px;">
            💡 Dica de Ouro
        </h3>
        <p style="margin-bottom: 10px; color: #444;">A página inicial pode ser completamente customizada de duas formas:</p>
        <ol style="margin: 0; padding-left: 20px; color: #444;">
            <li style="margin-bottom: 5px;">Editando diretamente o arquivo <code>themes/default/home.php</code>.</li>
            <li>Ou criando um novo artigo com a url/slug igual a <strong><code>home</code></strong> no painel (o sistema priorizará o artigo ao invés desta tela padrão).</li>
        </ol>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 40px;">
        <!-- Categorias -->
        <section style="flex: 1; min-width: 300px;">
            <h3 style="border-bottom: 2px solid #007bff; padding-bottom: 10px; color: #333;">📁 Categorias</h3>
            <?php 
            $categories = \Typper\Category::getCategories();
            if (empty($categories)): 
            ?>
                <p style="color: #777;">Nenhuma categoria cadastrada.</p>
            <?php else: ?>
                <div style="display: grid; gap: 15px;">
                    <?php foreach ($categories as $cat): ?>
                        <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?: '/' ?>/<?= htmlspecialchars($cat->slug) ?>" 
                           style="display: block; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 8px; text-decoration: none; color: inherit; transition: transform 0.2s, box-shadow 0.2s;"
                           onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)';"
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                            <h4 style="margin: 0 0 5px 0; color: #007bff; font-size: 1.2em;"><?= htmlspecialchars($cat->title) ?></h4>
                            <?php if (!empty($cat->description)): ?>
                                <p style="margin: 0; color: #666; font-size: 0.9em;"><?= htmlspecialchars($cat->description) ?></p>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Conteúdos sem categoria -->
        <section style="flex: 1; min-width: 300px;">
            <h3 style="border-bottom: 2px solid #28a745; padding-bottom: 10px; color: #333;">📄 Artigos Recentes</h3>
            <?php 
            $uncategorized = get_uncategorized_contents();
            // Ordenar do mais recente para o mais antigo
            usort($uncategorized, function($a, $b) {
                return strtotime($b->creationDate) - strtotime($a->creationDate);
            });
            
            if (empty($uncategorized)): 
            ?>
                <p style="color: #777;">Nenhum conteúdo encontrado.</p>
            <?php else: ?>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php foreach ($uncategorized as $item): ?>
                        <li style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                            <h4 style="margin: 0 0 5px 0; font-size: 1.2em;">
                                <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?: '/' ?>/<?= htmlspecialchars($item->slug) ?>" style="color: #222; text-decoration: none;">
                                    <?= htmlspecialchars($item->title ?? 'Sem título') ?>
                                </a>
                            </h4>
                            <div class="meta-info" style="font-size: 0.85em; color: #888;">
                                <span>📅 <?= date('d/m/Y', strtotime($item->creationDate)) ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include_once __DIR__ . '/footer.php'; ?>