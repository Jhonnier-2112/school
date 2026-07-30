<?php require_once __DIR__ . '/layouts/header.php'; ?>
<link rel="stylesheet" href="/assets/css/products.css">

<section class="py-4" style="margin-top: 100px;">
  <div class="container">
    <?php
      // Variables básicas
      $name = htmlspecialchars($p['name'] ?? 'Producto');
      $price = isset($p['price']) ? (float)$p['price'] : 0;
      $desc = isset($p['description']) ? trim((string)$p['description']) : '';
      $slug = isset($p['slug']) ? (string)$p['slug'] : '';
      $imgRel = isset($p['image']) ? trim((string)$p['image']) : '';
      $category = isset($p['category']) ? strtolower(trim((string)$p['category'])) : '';
      $type = isset($p['type']) ? strtolower(trim((string)$p['type'])) : '';
      $slugNorm = strtolower(trim((string)$slug));
      // Detectar accesorio temprano para usar en construcción de slides
      $isAccessory = ($category === 'accesorios')
        || str_contains($slugNorm, 'termo')
        || str_contains($slugNorm, 'flask')
        || str_contains($slugNorm, 'botella');
      // Solo textil: camisetas y esqueletos
      $isTextilType = in_array($type, ['camisetas', 'esqueletos'], true);

      // Resolver imágenes soportando assets en /public/assets y también en /assets
      $baseCandidates = [
        __DIR__ . '/../../public/',
        rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/') . '/'
      ];
      $existsRel = function($rel) use ($baseCandidates) {
        $rel = ltrim($rel, '/');
        foreach ($baseCandidates as $base) { if ($base !== '' && is_file($base . $rel)) return true; }
        return false;
      };
      $slugU = str_replace('-', '_', $slug);
      $finalImg = '';
      $backImg = '';

      $candidates = [];
      if ($imgRel) { $candidates[] = ltrim($imgRel, '/'); }
      foreach (['jpg','jpeg','png','svg'] as $ext) {
        $candidates[] = "assets/img/products/{$slug}.{$ext}";
        $candidates[] = "assets/img/products/{$slugU}.{$ext}";
        // Soportar nombre 'frontal' como imagen principal
        $candidates[] = "assets/img/products/{$slug}_frontal.{$ext}";
        $candidates[] = "assets/img/products/{$slugU}_frontal.{$ext}";
        if ($slug === 'camiseta_oficial_femtribe') {
          $candidates[] = "assets/img/products/camiseta_ofical_femtribe.{$ext}";
        }
        if ($slug === 'esqueleto_limite_run_2025_femtribe') {
          $candidates[] = "assets/img/products/esqueletos_femtribe.{$ext}";
          $candidates[] = "assets/img/products/esqueleto_femtribe.{$ext}";
        }
      }
      foreach ($candidates as $rel) {
        if ($existsRel($rel)) { $finalImg = $rel; break; }
      }

      $backCandidates = [];
      foreach (['jpg','jpeg','png','svg'] as $ext) {
        $backCandidates[] = "assets/img/products/{$slug}_back.{$ext}";
        $backCandidates[] = "assets/img/products/{$slugU}_back.{$ext}";
        $backCandidates[] = "assets/img/products/{$slug}-back.{$ext}";
        $backCandidates[] = "assets/img/products/{$slugU}-back.{$ext}";
        $backCandidates[] = "assets/img/products/{$slug}_trasera.{$ext}";
        $backCandidates[] = "assets/img/products/{$slugU}_trasera.{$ext}";
        $backCandidates[] = "assets/img/products/{$slug}-trasera.{$ext}";
        $backCandidates[] = "assets/img/products/{$slugU}-trasera.{$ext}";
        $backCandidates[] = "assets/img/products/{$slug}_black.{$ext}";
        $backCandidates[] = "assets/img/products/{$slugU}_black.{$ext}";
        $backCandidates[] = "assets/img/products/{$slug}-black.{$ext}";
        $backCandidates[] = "assets/img/products/{$slugU}-black.{$ext}";
        if ($slug === 'camiseta_oficial_femtribe') {
          $backCandidates[] = "assets/img/products/camiseta_ofical_femtribe_black.{$ext}";
        }
        if ($slug === 'esqueleto_limite_run_2025_femtribe') {
          $backCandidates[] = "assets/img/products/esqueleto_femtribe_back.{$ext}";
          $backCandidates[] = "assets/img/products/esqueletos_femtribe_back.{$ext}";
        }
      }
      if (!empty($finalImg)) {
        $frontNoExt = preg_replace('/\.(jpg|jpeg|png)$/i', '', $finalImg);
        foreach (['jpg','jpeg','png'] as $ext) {
          $backCandidates[] = $frontNoExt . '_back.' . $ext;
          $backCandidates[] = str_replace('_', '-', $frontNoExt) . '-back.' . $ext;
          $backCandidates[] = str_replace('-', '_', $frontNoExt) . '_back.' . $ext;
          // Si la frontal termina en '_frontal', probar el equivalente '_back'
          $backCandidates[] = preg_replace('/(_|-)frontal$/i', '$1back', $frontNoExt) . '.' . $ext;
          $backCandidates[] = $frontNoExt . '_trasera.' . $ext;
          $backCandidates[] = $frontNoExt . '_black.' . $ext;
          $backCandidates[] = str_replace('_', '-', $frontNoExt) . '-black.' . $ext;
          $backCandidates[] = str_replace('-', '_', $frontNoExt) . '_black.' . $ext;
        }
      }
      foreach ($backCandidates as $rel) {
        if ($existsRel($rel)) { $backImg = $rel; break; }
      }

      // Construir slides del carrusel: frontal, trasera, guía de tallas, ficha técnica (si existen)
      $slides = [];
      if (!empty($finalImg)) {
        $slides[] = ['src' => $finalImg, 'label' => 'Frontal'];
      }
      if (!empty($backImg)) {
        $slides[] = ['src' => $backImg, 'label' => 'Trasera'];
      }

      // Imágenes extra específicas y ordenadas por sufijo numérico o 'extra_N'
      $extraCandidates = [];
      foreach(['jpg','jpeg','png','svg'] as $ext) {
        // Permite {slug}_1..{slug}_8 y {slug}_extra_1..{slug}_8
        foreach (range(1,8) as $n) {
          $extraCandidates[] = "assets/img/products/{$slug}_{$n}.{$ext}";
          $extraCandidates[] = "assets/img/products/{$slugU}_{$n}.{$ext}";
          $extraCandidates[] = "assets/img/products/{$slug}_extra_{$n}.{$ext}";
          $extraCandidates[] = "assets/img/products/{$slugU}_extra_{$n}.{$ext}";
        }
        // Fallback explícito para 'termo_X' SOLO si el producto es 'termo'
        if (strtolower((string)$slug) === 'termo') {
          foreach ([3,4,5] as $n) {
            $extraCandidates[] = "assets/img/products/termo_{$n}.{$ext}";
          }
        }
      }
      foreach ($extraCandidates as $rel) {
        if ($existsRel($rel)) { $slides[] = ['src' => $rel, 'label' => 'Extra']; }
      }

      // Buscar guía de tallas
      $guideImg = '';
      $guideCandidates = [];
      foreach(['jpg','jpeg','png','svg'] as $ext) {
        $guideCandidates[] = "assets/img/products/{$slug}_guia_tallas.{$ext}";
        $guideCandidates[] = "assets/img/products/{$slugU}_guia_tallas.{$ext}";
        $guideCandidates[] = "assets/img/products/{$slug}_guia.{$ext}";
        $guideCandidates[] = "assets/img/products/{$slugU}_guia.{$ext}";
        $guideCandidates[] = "assets/img/products/{$slug}_size_guide.{$ext}";
        $guideCandidates[] = "assets/img/products/{$slugU}_size_guide.{$ext}";
      }
      foreach ($guideCandidates as $rel) {
        if ($existsRel($rel)) { $guideImg = $rel; break; }
      }
      if (!$isAccessory && !empty($guideImg)) {
        $slides[] = ['src' => $guideImg, 'label' => 'Guía de tallas'];
      }

      // Buscar ficha técnica / ficha específica del esqueleto
      $specImg = '';
      $specCandidates = [];
      $slugNormAll = strtolower((string)$slug);
      $isSkeleton = str_contains($slugNormAll, 'esqueleto');
      foreach(['jpg','jpeg','png','svg'] as $ext) {
        if ($isSkeleton) {
          // Para el esqueleto: priorizar 'ficha_esqueleto' y NO usar 'ficha_tecnica' genérica
          $specCandidates[] = "assets/img/products/{$slug}_ficha_esqueleto.{$ext}";
          $specCandidates[] = "assets/img/products/{$slugU}_ficha_esqueleto.{$ext}";
          $specCandidates[] = "assets/img/products/ficha_esqueleto.{$ext}";
        } else {
          // Para otros productos: conservar lógica de 'ficha_tecnica' y variantes
          $specCandidates[] = "assets/img/products/{$slug}_ficha_tecnica.{$ext}";
          $specCandidates[] = "assets/img/products/{$slugU}_ficha_tecnica.{$ext}";
          // Soporte explícito para nombre con dos puntos
          $specCandidates[] = "assets/img/products/{$slug}_ficha:tecnica.{$ext}";
          $specCandidates[] = "assets/img/products/{$slugU}_ficha:tecnica.{$ext}";
          $specCandidates[] = "assets/img/products/{$slug}_ficha.{$ext}";
          $specCandidates[] = "assets/img/products/{$slugU}_ficha.{$ext}";
          $specCandidates[] = "assets/img/products/{$slug}_specs.{$ext}";
          $specCandidates[] = "assets/img/products/{$slugU}_specs.{$ext}";
          // Fallback global
          $specCandidates[] = "assets/img/products/ficha:tecnica.{$ext}"; // colon
          $specCandidates[] = "assets/img/products/ficha_tecnica.{$ext}"; // underscore
          $specCandidates[] = "assets/img/products/ficha-tecnica.{$ext}"; // hyphen
        }
      }
      foreach ($specCandidates as $rel) {
        if ($existsRel($rel)) { $specImg = $rel; break; }
      }
      if ($isTextilType && !empty($specImg)) {
        $slides[] = ['src' => $specImg, 'label' => $isSkeleton ? 'Ficha esqueleto' : 'Ficha técnica'];
      }

      // Agregar imágenes de tallas generales solo para textil
      if (!$isAccessory) {
        $sizeAdults = '';
        $sizeKids = '';
        $adultsCandidates = [];
        $kidsCandidates = [];
        foreach(['jpg','jpeg','png','svg'] as $ext) {
          // soportar guion y underscore
          $adultsCandidates[] = "assets/img/products/tallas-adultos.$ext";
          $adultsCandidates[] = "assets/img/products/tallas_adultos.$ext";
          $kidsCandidates[]   = "assets/img/products/tallas-kids.$ext";
          $kidsCandidates[]   = "assets/img/products/tallas_kids.$ext";
        }
        foreach ($adultsCandidates as $rel) {
          if ($existsRel($rel)) { $sizeAdults = $rel; break; }
        }
        foreach ($kidsCandidates as $rel) {
          if ($existsRel($rel)) { $sizeKids = $rel; break; }
        }
        if (!empty($sizeAdults)) {
          $slides[] = ['src' => $sizeAdults, 'label' => 'Tallas adultos'];
        }
        if (!empty($sizeKids)) {
          $slides[] = ['src' => $sizeKids, 'label' => 'Tallas kids'];
        }
      }

      // Deduplicar imágenes por ruta (evitar repetidos en el carrusel)
      if (!empty($slides)) {
        $seen = [];
        $slides = array_values(array_filter($slides, function($s) use (&$seen) {
          $key = strtolower(ltrim($s['src'] ?? '', '/'));
          if ($key === '') return false;
          if (isset($seen[$key])) return false;
          $seen[$key] = true;
          return true;
        }));
      }

      // Enriquecer cada slide con srcset si hay variantes de alta resolución
      foreach ($slides as &$s) {
        $srcRel = ltrim($s['src'], '/');
        $srcset = '';
        // Derivar base y extensión
        $baseNoExt = preg_replace('/\.(jpg|jpeg|png|svg)$/i', '', $srcRel);
        $ext = pathinfo($srcRel, PATHINFO_EXTENSION);
        $hrCandidates = [];
        foreach (['@2x', '_2x', '-2x', '_large', '-large', '_hd', '-hd'] as $suffix) {
          $hrCandidates[] = $baseNoExt . $suffix . '.' . $ext;
        }
        // Si original es jpg/png, añadir posible webp
        if (preg_match('/\.(jpg|jpeg|png)$/i', $srcRel)) {
          $hrCandidates[] = $baseNoExt . '.webp';
          foreach (['@2x', '_2x', '-2x', '_large', '-large', '_hd', '-hd'] as $suffix) {
            $hrCandidates[] = $baseNoExt . $suffix . '.webp';
          }
        }
        $found = [];
        foreach ($hrCandidates as $rel) {
          if ($existsRel($rel)) { $found[] = '/' . ltrim($rel, '/'); }
        }
        if (!empty($found)) {
          // Construir srcset simple: alta resolución 2x primero
          $srcset = implode(', ', array_map(function($u){
            return $u . ' 2x';
          }, $found));
          $s['srcset'] = $srcset;
          $s['sizes'] = '(min-width: 768px) 50vw, 90vw';
        }
      }
      unset($s);
    ?>

    <div class="breadcrumb small mb-3"><a href="/">Inicio</a> / <a href="/productos">Productos</a> / <?php echo $name; ?></div>

    <div class="row g-4">
      <div class="col-12 col-md-6">
        <div class="detail-gallery">
          <?php if (!empty($slides)) : ?>
            <div class="detail-main mb-3">
              <button class="nav-btn prev" aria-label="Imagen anterior">‹</button>
              <img id="product-main-image"
                   src="/<?php echo htmlspecialchars(ltrim($slides[0]['src'], '/')); ?>"
                   <?php if (!empty($slides[0]['srcset'])): ?>srcset="<?php echo htmlspecialchars($slides[0]['srcset']); ?>" sizes="<?php echo htmlspecialchars($slides[0]['sizes']); ?>"<?php endif; ?>
                   alt="<?php echo $name . ' ' . htmlspecialchars($slides[0]['label']); ?>"
                   class="img-fluid" />
              <button class="nav-btn next" aria-label="Imagen siguiente">›</button>
            </div>
            <div class="detail-thumbs">
              <?php foreach ($slides as $i => $s): ?>
                <button class="detail-thumb <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo (int)$i; ?>" aria-label="Ver <?php echo htmlspecialchars($s['label']); ?>">
                  <img src="/<?php echo htmlspecialchars(ltrim($s['src'], '/')); ?>"
                       <?php if (!empty($s['srcset'])): ?>srcset="<?php echo htmlspecialchars($s['srcset']); ?>" sizes="64px"<?php endif; ?>
                       alt="<?php echo $name . ' ' . htmlspecialchars($s['label']); ?>" />
                </button>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="alert alert-info" role="alert">
              <strong>Guía de tallas y ficha técnica:</strong> Consulta las últimas imágenes antes de comprar.
            </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <h1 class="h3 mb-2"><?php echo $name; ?></h1>
        <?php if (strtolower((string)$slug) === 'camiseta_oficial_carrera'): ?>
          <div class="mb-2"><span class="badge" style="background:#FFE08A; color:#3A3A3A; font-weight:700; border-radius:999px; padding:6px 10px;">Edición especial limitada</span></div>
        <?php endif; ?>
        <div class="mb-3"><span class="h5 fw-bold">$<?php echo number_format($price, 0, ',', '.'); ?></span></div>
        <?php
          // Detectar accesorio para cambiar formato de descripción y opciones
          $slugNorm = strtolower(trim((string)$slug));
          $isAccessory = ($category === 'accesorios')
            || str_contains($slugNorm, 'termo')
            || str_contains($slugNorm, 'flask')
            || str_contains($slugNorm, 'botella');
        ?>
        <?php if ($desc !== ''): ?>
          <?php
            // Formateo inteligente universal: párrafos + listas cuando la línea inicia con '-' o '•'
            $raw = preg_replace('/\r\n?/','\n',$desc);
            $lines = preg_split('/\n/mu', $raw);
            if ($lines === false) { $lines = preg_split('/\n/m', $raw); }
            $lines = array_values(array_filter(array_map(function($i){ return trim($i); }, (array)$lines)));
            $blocks = [];
            foreach ($lines as $line) {
              // Encabezados tipo "Características:" o "Cuidados:" sin viñeta
              if (preg_match('/^\s*(Características|Cuidados):\s*$/iu', $line, $m)) {
                $blocks[] = ['type' => 'h', 'text' => $m[1]];
                continue;
              }
              // Ítems de lista: comienzan con '-' o '•'
              if (preg_match('/^\s*(?:-|•)\s*(.+)$/u', $line, $m)) {
                $blocks[] = ['type' => 'li', 'text' => $m[1]];
                continue;
              }
              // Resto: párrafos normales
              $blocks[] = ['type' => 'p', 'text' => $line];
            }
          ?>
          <div class="mb-4" style="line-height: 1.7;">
            <?php $openList = false; foreach ($blocks as $b): ?>
              <?php if ($b['type'] === 'li'): ?>
                <?php if (!$openList): $openList = true; ?><ul style="margin: 0 0 0.8rem 1.2rem; line-height: 1.7;"><?php endif; ?>
                <li><?php echo htmlspecialchars($b['text']); ?></li>
              <?php else: ?>
                <?php if ($openList): $openList = false; ?></ul><?php endif; ?>
                <?php if ($b['type'] === 'h'): ?>
                  <p class="fw-semibold" style="margin-bottom: 0.6rem;"><?php echo htmlspecialchars($b['text']); ?>:</p>
                <?php else: ?>
                  <p style="margin-bottom: 0.8rem; text-align: justify; "><?php echo htmlspecialchars($b['text']); ?></p>
                <?php endif; ?>
              <?php endif; ?>
            <?php endforeach; if ($openList): ?></ul><?php endif; ?>
          </div>

          <?php
            // Añadir sección de características para textil (camisetas/esqueletos), tolerando singular/plural
            $typeNormLocal = strtolower(trim((string)$type));
            $isTextilTypeLocal = in_array($typeNormLocal, ['camisetas','camiseta','esqueletos','esqueleto'], true);
            if ($isTextilTypeLocal) {
              $slugNormLocal = strtolower(trim((string)$slug));
              // Lista base por defecto
              $defaultFeatures = [
                'Tela respirable y de secado rápido',
                'Protección solar UPF 30+',
                'Composición 90% poliéster y 10% spandex',
                'Ligera, elástica y cómoda para entrenar y competir',
                'Costuras planas para minimizar el roce'
              ];
              // Personalización por producto existente (acepta variantes de slug)
              $featureMap = [
                'camiseta_oficial_carrera' => [
                  'Tejido técnico respirable con secado rápido',
                  'Protección solar UPF 30+',
                  'Composición 90% poliéster / 10% spandex',
                  'Costuras planas anti-roce'
                ],
                'camiseta_oficial_femtribe' => [
                  'Tela liviana y respirable para entrenamiento',
                  'Elasticidad y comodidad para mayor rendimiento',
                  'Tecnología de secado rápido y absorción eficiente',
                  'Protección solar UPF 30+',
                  'Composición 90% poliéster / 10% spandex',
                  'Costuras planas anti-roce'
                ],
                'esqueleto_limite_run_2025_femtribe' => [
                  'Tejido ultra ligero ideal para clima cálido',
                  'Alta ventilación con secado rápido',
                  'Libertad de movimiento y confort',
                  'Composición 90% poliéster / 10% spandex',
                  'Propiedades antibacteriales que evitan malos olores y resistencia del color ante el sudor, la luz y el lavado'
                ],
                'esqueleto_limited_run_2025_femtribe' => [
                  'Tejido ultra ligero ideal para clima cálido',
                  'Alta ventilación con secado rápido',
                  'Libertad de movimiento y confort',
                  'Composición 90% poliéster / 10% spandex',
                  'Propiedades antibacteriales que evitan malos olores y resistencia del color ante el sudor, la luz y el lavado'
                ],
              ];
              $features = $featureMap[$slugNormLocal] ?? $defaultFeatures;
            }
          ?>
          <?php if (!empty($isTextilTypeLocal)): ?>
            <div class="mb-4" style="line-height: 1.7;">
              <p class="fw-semibold" style="margin-bottom: 0.6rem;">Características:</p>
              <ul style="margin: 0 0 0.8rem 1.2rem;">
                <?php foreach ($features as $f): ?>
                  <li><?php echo htmlspecialchars($f); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <?php
          $genderRaw = isset($p['gender']) ? strtolower(trim((string)$p['gender'])) : '';
          $defaultGender = ($genderRaw === 'mujer') ? 'mujer' : 'hombre';
        ?>
        <?php
          // Color fijo por producto
          $fixedColorName = 'Negro';
          $fixedColorHex = '#000000';
          // Mantener blancos/verde en casos específicos
          if ($slugNorm === 'camiseta_oficial_carrera' || str_contains($slugNorm, 'carrera')) {
            $fixedColorName = 'Blanco';
            $fixedColorHex = '#FFFFFF';
          } elseif ($slugNorm === 'esqueleto_limite_run_2025_femtribe' || str_contains($slugNorm, 'esqueleto')) {
            $fixedColorName = 'Verde';
            $fixedColorHex = '#87CC3E';
          }
        ?>
        <div class="product-options card card-body" style="border-radius:12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
          <h2 class="h6 mb-3">Selecciona tus opciones</h2>
          <div class="mb-3">
            <label class="form-label">Color</label>
            <div class="d-flex align-items-center gap-2 flex-wrap" id="fixedColor">
              <span class="color-swatch fixed" title="<?php echo htmlspecialchars($fixedColorName); ?>" style="background: <?php echo htmlspecialchars($fixedColorHex); ?>;<?php echo $fixedColorName==='Blanco'? ' border:1px solid #ddd;' : '' ?>"></span>
              <span class="small">Color: <strong><?php echo htmlspecialchars($fixedColorName); ?></strong></span>
            </div>
          </div>

          <div class="mb-3">
            <label for="qty" class="form-label">Cantidad</label>
            <div class="qty-control">
              <button type="button" class="qty-btn minus" aria-label="Menos">−</button>
              <input id="qty" type="number" class="form-control qty-input" value="1" min="1" max="20">
              <button type="button" class="qty-btn plus" aria-label="Más">+</button>
            </div>
          </div>

          <?php if (!$isAccessory): ?>
            <div class="mb-3">
              <label class="form-label d-block">Género</label>
              <div class="btn-group" role="group" aria-label="Seleccionar género" id="genderGroup">
                <input type="radio" class="btn-check" name="gender" id="genderKids" autocomplete="off" value="kids">
                <label class="btn btn-outline-dark" for="genderKids">Kids</label>

                <input type="radio" class="btn-check" name="gender" id="genderMen" autocomplete="off" value="hombre" <?php echo $defaultGender==='hombre'?'checked':''; ?> >
                <label class="btn btn-outline-dark" for="genderMen">Hombre</label>

                <input type="radio" class="btn-check" name="gender" id="genderWomen" autocomplete="off" value="mujer" <?php echo $defaultGender==='mujer'?'checked':''; ?> >
                <label class="btn btn-outline-dark" for="genderWomen">Mujer</label>
              </div>
            </div>

            <div class="mb-2">
              <label class="form-label">Talla</label>
              <div id="sizesKids" class="size-grid d-none" aria-label="Tallas Kids">
                <button type="button" class="size-chip" data-size="14">14</button>
                <button type="button" class="size-chip" data-size="16">16</button>
                <button type="button" class="size-chip" data-size="18">18</button>
              </div>
              <div id="sizesMen" class="size-grid <?php echo $defaultGender==='hombre'? '': 'd-none'; ?>" aria-label="Tallas Hombre">
                <button type="button" class="size-chip" data-size="XS">XS</button>
                <button type="button" class="size-chip" data-size="S">S</button>
                <button type="button" class="size-chip" data-size="M">M</button>
                <button type="button" class="size-chip" data-size="L">L</button>
                <button type="button" class="size-chip" data-size="XL">XL</button>
                <button type="button" class="size-chip" data-size="XXL">XXL</button>
              </div>
              <div id="sizesWomen" class="size-grid <?php echo $defaultGender==='mujer'? '': 'd-none'; ?>" aria-label="Tallas Mujer">
                <button type="button" class="size-chip" data-size="XS">XS</button>
                <button type="button" class="size-chip" data-size="S">S</button>
                <button type="button" class="size-chip" data-size="M">M</button>
                <button type="button" class="size-chip" data-size="L">L</button>
                <button type="button" class="size-chip" data-size="XL">XL</button>
              </div>
            </div>
          <?php endif; ?>

          <div class="small text-muted" id="selectionSummary"></div>
          <div class="small text-danger mt-1 d-none" id="selectionError"></div>
          <div class="mt-3">
            <button type="button" id="addToCart" aria-label="Agregar al carrito"
              style="background:#87CC3E; color:#000; font-weight:700; padding:12px 20px; border:none; border-radius:10px;">
              Agregar al carrito
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if (!empty($slides)) : ?>
<script>
  (function(){
    const slides = <?php echo json_encode(array_map(function($s){ return ['src' => '/' . ltrim($s['src'], '/'), 'label' => $s['label']]; }, $slides), JSON_UNESCAPED_SLASHES); ?>;
    const productName = <?php echo json_encode($name); ?>;
    let current = 0;
    const mainImg = document.getElementById('product-main-image');
    const container = document.querySelector('.detail-main');
    const thumbBtns = document.querySelectorAll('.detail-thumb');
    function setSlide(idx){
      if (!slides[idx]) return;
      current = idx;
      mainImg.src = slides[idx].src;
      mainImg.alt = productName + ' ' + slides[idx].label;
      thumbBtns.forEach((b,i)=> b.classList.toggle('active', i === idx));
      // Al cambiar de imagen, desactivar zoom por clic
      if(container) container.classList.remove('clicked-zoom');
    }
    thumbBtns.forEach(btn => btn.addEventListener('click', function(){
      const idx = parseInt(this.dataset.index, 10);
      setSlide(idx);
    }));
    const prev = document.querySelector('.detail-main .prev');
    const next = document.querySelector('.detail-main .next');
    prev && prev.addEventListener('click', function(){ setSlide((current - 1 + slides.length) % slides.length); });
    next && next.addEventListener('click', function(){ setSlide((current + 1) % slides.length); });

    // Al pasar el cursor sobre flechas, desactivar zoom por hover
    [prev, next].forEach(btn => {
      if(!btn || !container) return;
      btn.addEventListener('mouseenter', () => container.classList.add('hovering-nav'));
      btn.addEventListener('mouseleave', () => container.classList.remove('hovering-nav'));
      // También al hacer clic en flechas, quitar zoom por clic
      btn.addEventListener('click', () => {
        container.classList.remove('clicked-zoom');
        resetZoomTransform();
      });
    });

    // Zoom al clic sobre la imagen: toggle
    if(mainImg && container){
      mainImg.addEventListener('click', () => {
        if(container.classList.contains('clicked-zoom')){
          container.classList.remove('clicked-zoom');
          resetZoomTransform();
        } else {
          container.classList.add('clicked-zoom');
          // En modo zoom por clic, permitir arrastre dentro del recuadro
          // El transform inicial lo aplica CSS (scale); al arrastrar usaremos translate+scale
        }
      });
    }

    // --- Arrastre (pan) dentro del recuadro cuando está zoom por clic ---
    let isDragging = false;
    let startX = 0, startY = 0;
    let offsetX = 0, offsetY = 0;
    const SCALE_CLICK = 1.8;

    function clampOffsets(){
      if(!mainImg) return;
      const baseW = mainImg.clientWidth; // tamaño render sin transform aplicado inline
      const baseH = mainImg.clientHeight;
      const maxX = (baseW * SCALE_CLICK - baseW) / 2;
      const maxY = (baseH * SCALE_CLICK - baseH) / 2;
      offsetX = Math.max(-maxX, Math.min(maxX, offsetX));
      offsetY = Math.max(-maxY, Math.min(maxY, offsetY));
    }
    function applyTransform(){
      mainImg.style.transform = `translate(${offsetX}px, ${offsetY}px) scale(${SCALE_CLICK})`;
    }
    function resetZoomTransform(){
      isDragging = false; offsetX = 0; offsetY = 0; startX = 0; startY = 0;
      if(mainImg){
        mainImg.style.transform = '';
        mainImg.style.cursor = 'zoom-in';
      }
    }

    if(mainImg){
      mainImg.draggable = false;
      mainImg.addEventListener('dragstart', e => e.preventDefault());

      mainImg.addEventListener('pointerdown', (e) => {
        if(!container.classList.contains('clicked-zoom')) return;
        isDragging = true;
        startX = e.clientX; startY = e.clientY;
        try { mainImg.setPointerCapture(e.pointerId); } catch(_){}
        mainImg.style.cursor = 'grabbing';
        e.preventDefault();
      });
      // Inercia: medir velocidad durante el arrastre y aplicar momentum al soltar
      let vx = 0, vy = 0; // píxeles por ms
      let lastT = 0;
      mainImg.addEventListener('pointermove', (e) => {
        if(!isDragging) return;
        const now = performance.now();
        if(lastT === 0) lastT = now;
        const dt = Math.max(1, now - lastT); // evitar división por cero
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        startX = e.clientX; startY = e.clientY;
        lastT = now;
        offsetX += dx; offsetY += dy;
        // velocidad en px/ms
        vx = dx / dt; vy = dy / dt;
        clampOffsets();
        applyTransform();
      });
      function startMomentum(){
        let running = true;
        let prev = performance.now();
        const friction = 0.92; // coeficiente de frenado por frame
        const minSpeed = 0.02; // px/ms
        function step(ts){
          if(!running || !container.classList.contains('clicked-zoom')) return;
          const dt = Math.max(1, ts - prev);
          prev = ts;
          // aplicar movimiento en función de velocidad
          offsetX += vx * dt * 1.0;
          offsetY += vy * dt * 1.0;
          // fricción exponencial
          vx *= friction; vy *= friction;
          // si tocamos límites, amortiguar más y anular componente hacia fuera
          const beforeX = offsetX, beforeY = offsetY;
          clampOffsets();
          if(Math.abs(offsetX - beforeX) > 0.1){ vx = 0; }
          if(Math.abs(offsetY - beforeY) > 0.1){ vy = 0; }
          applyTransform();
          if(Math.abs(vx) < minSpeed && Math.abs(vy) < minSpeed){ running = false; return; }
          requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
      }
      const endDrag = () => {
        if(!isDragging) return;
        isDragging = false;
        lastT = 0;
        // iniciar momentum si aún estamos en zoom por clic
        if(container.classList.contains('clicked-zoom')){
          mainImg.style.cursor = 'grab';
          startMomentum();
        }
      };
      mainImg.addEventListener('pointerup', endDrag);
      mainImg.addEventListener('pointerleave', endDrag);

      // Doble clic para entrar/salir del zoom
      mainImg.addEventListener('dblclick', () => {
        if(container.classList.contains('clicked-zoom')){
          container.classList.remove('clicked-zoom');
          resetZoomTransform();
        } else {
          container.classList.add('clicked-zoom');
        }
      });
    }

    // --- Opciones: color, cantidad, género y tallas ---
    const fixedColor = <?php echo json_encode($fixedColorName); ?>;
    const colorPicker = document.getElementById('fixedColor');
    const genderGroup = document.getElementById('genderGroup');
    const sizesKids = document.getElementById('sizesKids');
    const sizesMen = document.getElementById('sizesMen');
    const sizesWomen = document.getElementById('sizesWomen');
    const summary = document.getElementById('selectionSummary');
    const errorBox = document.getElementById('selectionError');
    function updateButtonState(){
      try {
        const btn = document.getElementById('addToCart');
        if (btn) btn.disabled = !isValidSelection();
      } catch(_){}
    }
    let selectedColor = fixedColor || 'Negro';
    const isAccessory = <?php echo json_encode((bool)$isAccessory); ?>;
    let selectedGender = isAccessory ? '' : (document.querySelector('input[name="gender"]:checked')?.value || '');
    let selectedSize = '';

    function updateSizesVisibility(){
      sizesKids.classList.toggle('d-none', selectedGender !== 'kids');
      sizesMen.classList.toggle('d-none', selectedGender !== 'hombre');
      sizesWomen.classList.toggle('d-none', selectedGender !== 'mujer');
      // reset selection when switching group
      document.querySelectorAll('.size-chip.selected').forEach(b => b.classList.remove('selected'));
      selectedSize = '';
      renderSummary();
      renderValidation();
    }
    function renderSummary(){
      if (isAccessory) {
        summary.textContent = `Color: ${selectedColor}`;
      } else {
        summary.textContent = `Color: ${selectedColor} • Género: ${selectedGender} ${selectedSize? '• Talla: '+selectedSize : ''}`;
      }
    }

    function isValidSelection(){
      if (isAccessory) return true;
      const hasGender = !!selectedGender;
      const hasSize = !!selectedSize;
      return hasGender && hasSize;
    }

    function renderValidation(){
      const ok = isValidSelection();
      if (!ok) {
        let msg = '';
        if (!selectedGender && !selectedSize) {
          msg = 'Selecciona el género y la talla antes de agregar.';
        } else if (!selectedGender) {
          msg = 'Selecciona el género antes de agregar.';
        } else if (!selectedSize) {
          msg = 'Selecciona la talla antes de agregar.';
        }
        if (errorBox) { errorBox.textContent = msg; errorBox.classList.remove('d-none'); }
      } else {
        if (errorBox) { errorBox.textContent = ''; errorBox.classList.add('d-none'); }
      }
      updateButtonState();
    }
    // Color fijo: no hay interacción
    if (!isAccessory) {
      genderGroup?.addEventListener('change', (e) => {
        const r = e.target.closest('input[name="gender"]');
        if(!r) return;
        selectedGender = r.value;
        updateSizesVisibility();
      });
      document.querySelectorAll('.size-grid .size-chip').forEach(btn => {
        btn.addEventListener('click', () => {
          const grid = btn.parentElement;
          grid.querySelectorAll('.size-chip').forEach(b => b.classList.remove('selected'));
          btn.classList.add('selected');
          selectedSize = btn.dataset.size || '';
          renderSummary();
          renderValidation();
          updateButtonState();
        });
      });
      // inicialización
      updateSizesVisibility();
    }
    // Color fijo: marcado visual
    colorPicker?.querySelector('.color-swatch')?.classList.add('selected');
    renderSummary();
    renderValidation();
    updateButtonState();
    // Controles de cantidad +/-
    const qtyInput = document.getElementById('qty');
    const minusBtn = document.querySelector('.qty-btn.minus');
    const plusBtn = document.querySelector('.qty-btn.plus');
    function clampQty(val){
      const min = parseInt(qtyInput.min || '1', 10);
      const max = parseInt(qtyInput.max || '20', 10);
      return Math.max(min, Math.min(max, val));
    }
    minusBtn?.addEventListener('click', () => {
      const cur = clampQty(parseInt(qtyInput.value || '1', 10) - 1);
      qtyInput.value = cur;
    });
    plusBtn?.addEventListener('click', () => {
      const cur = clampQty(parseInt(qtyInput.value || '1', 10) + 1);
      qtyInput.value = cur;
    });

    // --- Agregar al carrito (localStorage) ---
    const addBtn = document.getElementById('addToCart');
    const STORAGE_KEY = 'ft_cart';
    const productSlug = <?php echo json_encode($slug); ?>;
    const productPrice = <?php echo json_encode($price); ?>;
    function readCart(){
      try { const raw = localStorage.getItem(STORAGE_KEY); return raw ? JSON.parse(raw) : []; } catch(e){ return []; }
    }
    function writeCart(items){
      localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
      const badge = document.querySelector('[data-cart-count]');
      if (badge) badge.textContent = items.reduce((a,i)=> a + Number(i.qty||0), 0);
    }
    addBtn?.addEventListener('click', () => {
      // Validación: impedir agregar si falta género o talla en textil
      if (!isValidSelection()) {
        renderValidation();
        return;
      }
      const qty = clampQty(parseInt(qtyInput.value || '1', 10));
      const size = isAccessory ? '' : (selectedSize || '');
      const gender = isAccessory ? '' : (selectedGender || '');
      const color = selectedColor || '';
      const item = { slug: productSlug, name: productName, price: Number(productPrice||0), qty, color, gender, size };
      const items = readCart();
      // Unificar por slug+opciones
      const idx = items.findIndex(i => i.slug === item.slug && (i.color||'') === item.color && (i.gender||'') === item.gender && (i.size||'') === item.size);
      if (idx >= 0) {
        items[idx].qty = Number(items[idx].qty || 0) + item.qty;
      } else {
        items.push(item);
      }
      writeCart(items);
      // Feedback sencillo
      try {
        const btn = addBtn; btn.disabled = true; btn.textContent = 'Agregado';
        setTimeout(()=>{ btn.disabled = false; btn.textContent = 'Agregar al carrito'; }, 1200);
      } catch(_){}
    });
  })();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>