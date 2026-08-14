# Typper CMS

Typper é um CMS (Content Management System) minimalista baseado em arquivos Markdown, possuindo um painel web e comandos CLI para a criação dos conteúdos. Desenvolvido para ser rápido, leve e sem dependência de bancos de dados tradicionais, todo o conteúdo é gerenciado através de arquivos `.md` e as configurações através de `.yml`.

## 🚀 Painel de Controle (Admin Panel)

O Typper possui um painel administrativo completo, moderno e responsivo, acessível pelo navegador.

Para acessar o painel, basta navegar até a pasta `/panel` da sua instalação.
**Exemplo:** `http://seusite.com/panel`

No painel você consegue:

- Criar, gerenciar e editar conteúdos em Markdown.
- Gerenciar imagens e mídias de cada artigo de forma independente.
- Configurar informações do site, tema ativo e metadados dinâmicos de SEO.
- Configurar seu código de rastreamento do Google Analytics (GA4).

> O painel funciona como um **PWA (Progressive Web App)**! Você pode adicionar o Typper à tela inicial do seu celular ou instalar no Desktop (via Chrome/Edge) para usar em modo tela cheia, como um aplicativo nativo.

## 💻 Comandos de Terminal (CLI)

Além do painel, o Typper inclui um script de linha de comando. Você pode utilizá-lo no terminal para gerenciar o site rapidamente:

- `php typper.php site --title="Meu Site" --author="Autor" --theme="default"` - Cria ou altera as propriedades do arquivo `site.yml`.
- `php typper.php make:post [slug] --category="nome"` - Cria um novo post (artigo).
- `php typper.php list:posts --category="nome"` - Lista os posts da categoria. Se a flag não for passada, lista posts sem categoria.
- `php typper.php make:page [slug] --category="nome"` - Cria uma nova página.
- `php typper.php list:pages --category="nome"` - Lista as páginas. Se a flag não for passada, lista páginas sem categoria.
- `php typper.php make:category [slug] --title="Nome" --desc="Descrição"` - Cria uma nova categoria.
- `php typper.php edit:category [slug] --title="Novo Nome"` - Edita uma categoria existente.
- `php typper.php list:categories` - Lista as categorias existentes.
- `php typper.php --clear` (ou `-c`) - Limpa todo o cache do sistema.
- `php typper.php delete [file]` - Deleta o cache de um arquivo específico.

Os conteúdos criados serão armazenados automaticamente na pasta `/contents/` e já virão com o cabeçalho YAML (front-matter) básico estruturado.

## 🎥 Como Inserir Vídeos no Conteúdo

O Typper suporta a incorporação (embed) responsiva nativa de diversos players de vídeo.

Para adicionar um vídeo ao seu artigo no editor, basta utilizar a sintaxe de blocos de código do Markdown (três crases), definindo a plataforma do vídeo como "linguagem", e informando o **ID do vídeo** (ou URL) dentro do bloco.

**YouTube:**

````markdown
```youtube
aBcD1234ZxY
```
````

**Vimeo:**

````markdown
```vimeo
123456789
```
````

**Vídeos Locais (MP4):**

````markdown
```mp4
https://seusite.com/caminho/para/video.mp4
```
````

## 🖼️ Redimensionamento Dinâmico de Imagens

O Typper suporta compressão e redimensionamento dinâmico no momento da requisição, poupando dados do usuário e melhorando a pontuação no Core Web Vitals.

Basta adicionar o parâmetro `?resize=LARGURA` ao final de qualquer imagem:

```markdown
![Minha imagem](/typper/files/post/imagem.jpg?resize=800)
```

*(Opcional: Você pode incluir `&quality=70` para forçar o nível de qualidade).*

## 🛠️ Tecnologias

- [PHP 8.x](https://php.net/)
- [ParsedownExtra](https://github.com/erusev/parsedown-extra)
- [Toast UI (TUI) Editor 3.x](https://ui.toast.com/tui-editor)
- [Cocur Slugify](https://github.com/cocur/slugify)
