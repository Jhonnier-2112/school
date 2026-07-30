<?php require_once __DIR__ . '/layouts/header.php'; ?>
<link rel="stylesheet" href="/assets/css/products.css">

<section class="py-4" style="margin-top: 100px;">
    <div class="container">
        <?php
            $total = isset($pagination['total']) ? (int)$pagination['total'] : 0;
            $order = $_GET['order'] ?? 'created_at DESC';
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 12;
            $options = [
                'created_at DESC' => 'Más nuevos',
                'price ASC' => 'Precio: menor a mayor',
                'price DESC' => 'Precio: mayor a menor',
                'name ASC' => 'Nombre A–Z',
            ];
            $currentCategory = $_GET['category'] ?? null;
            $baseQs = $_GET;
            unset($baseQs['category'], $baseQs['gender'], $baseQs['type']);
            $hrefTodos = '?' . http_build_query($baseQs);
            // Ropa => category = textil
            $qsRopa = $baseQs; $qsRopa['category'] = 'textil'; unset($qsRopa['type'], $qsRopa['gender']);
            $hrefRopa = '?' . http_build_query($qsRopa);
            // Accesorios => category = accesorios
            $qsAcc = $baseQs; $qsAcc['category'] = 'accesorios'; unset($qsAcc['type'], $qsAcc['gender']);
            $hrefAccesorios = '?' . http_build_query($qsAcc);
        ?>

        <!-- Banner superior: imagen fija con enlace al detalle de producto -->
        <div class="mb-3">
            <a href="/producto?slug=camiseta_oficial_carrera" class="d-block" aria-label="Ver detalles de la camiseta de carrera">
                <img src="/assets/img/banner_camiseta_carrera.png" alt="Banner camiseta oficial de carrera" class="catalog-banner-img" />
            </a>
        </div>

        <!-- Layout con filtros a la izquierda y contenido a la derecha -->
        <div class="catalog-layout">
            <aside class="filters-sidebar">
                <div class="breadcrumb small mb-2"><a href="/">Inicio</a> / Productos</div>
                <h2 class="filters-title">Filtros</h2>
                <?php
                    $orderQs = $_GET['order'] ?? null;
                    $perQs = isset($_GET['per_page']) ? (int)$_GET['per_page'] : null;
                ?>
                <form method="get" class="filters-form" aria-label="Filtros de categoría">
                    <input type="hidden" name="page" value="1" />
                    <?php if ($orderQs) : ?><input type="hidden" name="order" value="<?php echo htmlspecialchars($orderQs); ?>" /><?php endif; ?>
                    <?php if ($perQs) : ?><input type="hidden" name="per_page" value="<?php echo htmlspecialchars($perQs); ?>" /><?php endif; ?>
                    <input type="hidden" name="category" id="filter-category-hidden" value="<?php echo htmlspecialchars($currentCategory ?? ''); ?>" />
                    <?php
                        // Subfiltros actuales
                        $currentTypes = [];
                        if (!empty($_GET['type'])) {
                            $currentTypes = is_array($_GET['type']) ? $_GET['type'] : [$_GET['type']];
                            $currentTypes = array_map(function($t){ return strtolower((string)$t); }, $currentTypes);
                        }
                    ?>
                    <?php
                        // Ocultamos temporalmente tipos no usados: licras y medias
                        $ropaTypes = ['camisetas','esqueletos'];
                        $accTypes = ['botella_plegable'];
                        $hasTextilSelected = count(array_intersect($currentTypes ?? [], $ropaTypes)) > 0;
                        $hasAccesoriosSelected = count(array_intersect($currentTypes ?? [], $accTypes)) > 0;
                    ?>
                    <div class="filter-group">
                        <div class="filter-header" role="button" tabindex="0" aria-expanded="<?php echo $hasTextilSelected ? 'true' : 'false'; ?>">
                            <span>Ropa</span>
                            <span class="chevron">▾</span>
                        </div>
                        <div class="filter-body <?php echo $hasTextilSelected ? 'open' : ''; ?>">
                            <div class="filters-list">
                                <?php foreach ($ropaTypes as $tVal): $tLabel = ucfirst($tVal); if ($tVal==='esqueletos') $tLabel='Esqueletos'; ?>
                                    <label class="filter-option">
                                        <input type="checkbox" name="type[]" data-group="textil" value="<?php echo $tVal; ?>" <?php echo in_array($tVal, $currentTypes) ? 'checked' : ''; ?> />
                                        <span><?php echo $tLabel; ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="filter-group">
                        <div class="filter-header" role="button" tabindex="0" aria-expanded="<?php echo $hasAccesoriosSelected ? 'true' : 'false'; ?>">
                            <span>Accesorios</span>
                            <span class="chevron">▾</span>
                        </div>
                        <div class="filter-body <?php echo $hasAccesoriosSelected ? 'open' : ''; ?>">
                            <div class="filters-list">
                                <label class="filter-option">
                                    <input type="checkbox" name="type[]" data-group="accesorios" value="botella_plegable" <?php echo in_array('botella_plegable', $currentTypes) ? 'checked' : ''; ?> />
                                    <span>Termo Plegable</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <?php
                        // Subfiltros de tipo (solo relevantes para categoría textil)
                        $currentTypes = [];
                        if (!empty($_GET['type'])) {
                            $currentTypes = is_array($_GET['type']) ? $_GET['type'] : [$_GET['type']];
                            $currentTypes = array_map(function($t){ return strtolower((string)$t); }, $currentTypes);
                        }
                    ?>
                    
                </form>
            </aside>

            <div class="products-content">
                <!-- Breadcrumb móvil (visible solo en pantallas pequeñas) -->
                <div class="breadcrumb small mb-2 mobile-breadcrumb"><a href="/">Inicio</a> / Productos</div>
                <!-- Topbar móvil: iconos de Filtros y Ordenar por -->
                <div class="mobile-topbar">
                    <button class="mobile-btn" id="openFiltersBtn" aria-label="Abrir filtros"><i class="fas fa-sliders-h"></i> <span>Filtros</span></button>
                    <button class="mobile-btn" id="openSortBtn" aria-label="Abrir ordenar"><i class="fas fa-sort"></i> <span>Ordenar por</span></button>
                </div>
                <div class="sorting-toolbar">
                    <div class="sorting-left">
                        <h1 class="content-title">Productos FemTribe</h1>
                        <p class="content-meta"><?php echo $total . ' producto' . ($total === 1 ? '' : 's'); ?></p>
                    </div>
                    <form method="get" class="sorting-controls">
                        <input type="hidden" name="page" value="1" />
                        <?php // Preservar categoría actual si aplica ?>
                        <?php if ($currentCategory !== null): ?>
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($currentCategory); ?>" />
                        <?php endif; ?>
                        <?php // Preservar type[] si existe ?>
                        <?php if (!empty($currentTypes)) : foreach ($currentTypes as $ct): ?>
                            <input type="hidden" name="type[]" value="<?php echo htmlspecialchars($ct); ?>" />
                        <?php endforeach; endif; ?>
                        <div class="control">
                            <label>Ordenar por</label>
                            <select name="order" class="form-select sorting-select">
                                <?php foreach ($options as $val => $label): ?>
                                    <option value="<?php echo $val; ?>" <?php echo $order === $val ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!--
                        <div class="control">
                            <label>Mostrar</label>
                            <select name="per_page" class="form-select sorting-select">
                                <?php foreach ([12,24,48] as $pp): ?>
                                    <option value="<?php echo $pp; ?>" <?php echo ($perPage === $pp) ? 'selected' : ''; ?>>
                                        <?php echo $pp; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        -->
                    </form>
                </div>

                <!-- Overlay modal: Filtros (móvil) -->
                <div class="overlay" id="filtersOverlay" aria-hidden="true">
                  <div class="overlay-panel">
                    <div class="overlay-header">
                      <span>Filtros</span>
                      <button class="overlay-close" type="button" aria-label="Cerrar">×</button>
                    </div>
                    <div class="overlay-body">
                      <form method="get" class="filters-form" aria-label="Filtros de categoría (móvil)">
                        <input type="hidden" name="page" value="1" />
                        <?php if (!empty($_GET['order'])): ?><input type="hidden" name="order" value="<?php echo htmlspecialchars($_GET['order']); ?>" /><?php endif; ?>
                        <?php if (!empty($_GET['per_page'])): ?><input type="hidden" name="per_page" value="<?php echo htmlspecialchars((int)$_GET['per_page']); ?>" /><?php endif; ?>
                        <input type="hidden" name="category" id="filter-category-hidden" value="<?php echo htmlspecialchars($currentCategory ?? ''); ?>" />
                        <?php
                          $currentTypesMobile = [];
                          if (!empty($_GET['type'])) {
                            $currentTypesMobile = is_array($_GET['type']) ? $_GET['type'] : [$_GET['type']];
                            $currentTypesMobile = array_map(function($t){ return strtolower((string)$t); }, $currentTypesMobile);
                          }
                          $hasTextilSelectedM = count(array_intersect($currentTypesMobile, $ropaTypes)) > 0;
                          $hasAccesoriosSelectedM = count(array_intersect($currentTypesMobile, $accTypes)) > 0;
                        ?>
                        <div class="filter-group">
                          <div class="filter-header" role="button" tabindex="0" aria-expanded="<?php echo $hasTextilSelectedM ? 'true' : 'false'; ?>">
                            <span>Tipo de prenda</span>
                            <span class="chevron">▾</span>
                          </div>
                          <div class="filter-body <?php echo $hasTextilSelectedM ? 'open' : ''; ?>">
                            <div class="filters-list">
                              <?php foreach ($ropaTypes as $tVal): $tLabel = ucfirst($tVal); if ($tVal==='esqueletos') $tLabel='Esqueletos'; ?>
                                <label class="filter-option">
                                  <input type="checkbox" name="type[]" data-group="textil" value="<?php echo $tVal; ?>" <?php echo in_array($tVal, $currentTypesMobile) ? 'checked' : ''; ?> />
                                  <span><?php echo $tLabel; ?></span>
                                </label>
                              <?php endforeach; ?>
                            </div>
                          </div>
                        </div>
                        <div class="filter-group">
                          <div class="filter-header" role="button" tabindex="0" aria-expanded="<?php echo $hasAccesoriosSelectedM ? 'true' : 'false'; ?>">
                            <span>Accesorios</span>
                            <span class="chevron">▾</span>
                          </div>
                          <div class="filter-body <?php echo $hasAccesoriosSelectedM ? 'open' : ''; ?>">
                            <div class="filters-list">
                              <label class="filter-option">
                                <input type="checkbox" name="type[]" data-group="accesorios" value="botella_plegable" <?php echo in_array('botella_plegable', $currentTypesMobile) ? 'checked' : ''; ?> />
                                <span>Termo Plegable</span>
                              </label>
                            </div>
                          </div>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <!-- Overlay modal: Ordenar por (móvil) -->
                <div class="overlay" id="sortOverlay" aria-hidden="true">
                  <div class="overlay-panel">
                    <div class="overlay-header">
                      <span>Ordenar por</span>
                      <button class="overlay-close" type="button" aria-label="Cerrar">×</button>
                    </div>
                    <div class="overlay-body">
                      <div class="sort-options">
                        <?php foreach ($options as $val => $label): ?>
                          <button class="sort-option" data-order="<?php echo htmlspecialchars($val); ?>" <?php echo ($order === $val) ? 'data-active="true"' : ''; ?>><?php echo htmlspecialchars($label); ?></button>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                </div>

                <?php
                    // Chips de filtros activos con opción de quitar
                    $qsAll = $_GET;
                    $labels = [];
                    if (!empty($qsAll['category'])) {
                        $cat = strtolower((string)$qsAll['category']);
                        $labels['category'] = ($cat === 'textil') ? 'Ropa' : (($cat === 'accesorios') ? 'Accesorios' : $cat);
                    }
                    // Para type[], crear chips individuales
                    $typeValues = [];
                    if (!empty($qsAll['type'])) {
                        $typeValues = is_array($qsAll['type']) ? $qsAll['type'] : [$qsAll['type']];
                        $typeValues = array_map(function($t){ return strtolower((string)$t); }, $typeValues);
                    }
                    if (!empty($qsAll['gender'])) { $labels['gender'] = ucfirst($qsAll['gender']); }
                    if (!empty($qsAll['min_price'])) { $labels['min_price'] = 'Min $' . number_format((float)$qsAll['min_price'], 0, ',', '.'); }
                    if (!empty($qsAll['max_price'])) { $labels['max_price'] = 'Max $' . number_format((float)$qsAll['max_price'], 0, ',', '.'); }
                ?>
                <?php if (!empty($labels) || !empty($typeValues)) : ?>
                    <div class="applied-filters mb-3">
                        <?php foreach ($labels as $key => $label):
                            $tmp = $qsAll; unset($tmp[$key]); $tmp['page'] = 1; $hrefRemove = '?' . http_build_query($tmp);
                        ?>
                            <a class="applied-chip" href="<?php echo $hrefRemove; ?>" title="Quitar filtro"><?php echo htmlspecialchars($label); ?> <span class="chip-x">×</span></a>
                        <?php endforeach; ?>
                        <?php if (!empty($typeValues)) : foreach ($typeValues as $tVal):
                            $tmp = $qsAll;
                            if (isset($tmp['type'])) {
                                $tmp['type'] = array_values(array_filter(is_array($tmp['type']) ? $tmp['type'] : [$tmp['type']], function($x) use ($tVal) { return strtolower((string)$x) !== $tVal; }));
                                if (count($tmp['type']) === 0) { unset($tmp['type']); }
                            }
                            $tmp['page'] = 1;
                            $hrefRemoveType = '?' . http_build_query($tmp);
                        ?>
                            <a class="applied-chip" href="<?php echo $hrefRemoveType; ?>" title="Quitar tipo"><?php echo ucfirst(str_replace('_',' ', $tVal)); ?> <span class="chip-x">×</span></a>
                        <?php endforeach; endif; ?>
                    <?php $tmpAll = $qsAll; unset($tmpAll['category'],$tmpAll['type'],$tmpAll['gender'],$tmpAll['min_price'],$tmpAll['max_price']); $tmpAll['page']=1; $hrefClearAll = '?' . http_build_query($tmpAll); ?>
                    <a class="applied-chip clear-all" href="<?php echo $hrefClearAll; ?>">Limpiar todo</a>
                    </div>
                <?php endif; ?>

                <!-- Inicio del listado y tarjetas -->

        

        <?php if (!empty($products)) : ?>
            <div class="row g-4">
                <?php foreach ($products as $p) : ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <?php 
                          $pType = isset($p['type']) ? strtolower((string)$p['type']) : '';
                          $isEsqueleto = ($pType === 'esqueletos');
                        ?>
                        <a href="/producto?slug=<?php echo urlencode((string)($p['slug'] ?? '')); ?>" class="card h-100 shadow-sm product-card <?php echo $isEsqueleto ? 'esqueleto-card' : ''; ?>" style="border-radius: 12px; overflow: hidden; text-decoration:none; color: inherit; display:block;">
                            <div class="position-relative image-box" style="aspect-ratio: 4 / 5;">
                                <?php
                                    // Fallback inteligente de imagen si no viene en BD
                                    $imgRel = isset($p['image']) ? trim($p['image']) : '';
                                    $imgRel = ltrim($imgRel, '/');
                                    $finalImg = '';
                                    $backImg = '';

                                    // Soportar assets en /public/assets y también en /assets según despliegue
                                    $baseCandidates = [
                                        __DIR__ . '/../../public/',
                                        rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/') . '/'
                                    ];
                                    $existsRel = function($rel) use ($baseCandidates) {
                                        $rel = ltrim($rel, '/');
                                        foreach ($baseCandidates as $base) {
                                            if ($base !== '' && is_file($base . $rel)) { return true; }
                                        }
                                        return false;
                                    };
                                    $slug = isset($p['slug']) ? $p['slug'] : '';
                                    $slugU = str_replace('-', '_', $slug);

                                    $candidates = [];
                                    if ($imgRel) {
                                        $candidates[] = $imgRel;
                                    }
                                    // Intentar por slug con diferentes extensiones
                                    foreach (['jpg','jpeg','png','svg'] as $ext) {
                                        $candidates[] = "assets/img/products/{$slug}.{$ext}";
                                        $candidates[] = "assets/img/products/{$slugU}.{$ext}";
                                        // Soportar nombre 'frontal' como imagen principal
                                        $candidates[] = "assets/img/products/{$slug}_frontal.{$ext}";
                                        $candidates[] = "assets/img/products/{$slugU}_frontal.{$ext}";
                                        // Fallback por typo histórico: "ofical" (sin i)
                                        if ($slug === 'camiseta_oficial_femtribe') {
                                            $candidates[] = "assets/img/products/camiseta_ofical_femtribe.{$ext}";
                                        }
                                        // Fallback para nombres genéricos de esqueleto
                                        if ($slug === 'esqueleto_limite_run_2025_femtribe') {
                                            $candidates[] = "assets/img/products/esqueletos_femtribe.{$ext}";
                                            $candidates[] = "assets/img/products/esqueleto_femtribe.{$ext}";
                                        }
                                    }

                                    foreach ($candidates as $rel) {
                                        if ($existsRel($rel)) { $finalImg = $rel; break; }
                                    }
                                    
                                    // Buscar imagen trasera (back) con variantes de nombre
                                    $backCandidates = [];
                                    foreach (['jpg','jpeg','png','svg'] as $ext) {
                                        // Variante con underscore
                                        $backCandidates[] = "assets/img/products/{$slug}_back.{$ext}";
                                        $backCandidates[] = "assets/img/products/{$slugU}_back.{$ext}";
                                        // Variante con guion
                                        $backCandidates[] = "assets/img/products/{$slug}-back.{$ext}";
                                        $backCandidates[] = "assets/img/products/{$slugU}-back.{$ext}";
                                        // Variante en español (trasera)
                                        $backCandidates[] = "assets/img/products/{$slug}_trasera.{$ext}";
                                        $backCandidates[] = "assets/img/products/{$slugU}_trasera.{$ext}";
                                        $backCandidates[] = "assets/img/products/{$slug}-trasera.{$ext}";
                                        $backCandidates[] = "assets/img/products/{$slugU}-trasera.{$ext}";
                                        // Variante color negro
                                        $backCandidates[] = "assets/img/products/{$slug}_black.{$ext}";
                                        $backCandidates[] = "assets/img/products/{$slugU}_black.{$ext}";
                                        $backCandidates[] = "assets/img/products/{$slug}-black.{$ext}";
                                        $backCandidates[] = "assets/img/products/{$slugU}-black.{$ext}";
                                        if ($slug === 'camiseta_oficial_femtribe') {
                                            // Fallback por typo histórico: negro con "ofical"
                                            $backCandidates[] = "assets/img/products/camiseta_ofical_femtribe_black.{$ext}";
                                        }
                                        // Fallback explícito para back en esqueleto
                                        if ($slug === 'esqueleto_limite_run_2025_femtribe') {
                                            $backCandidates[] = "assets/img/products/esqueleto_femtribe_back.{$ext}";
                                            $backCandidates[] = "assets/img/products/esqueletos_femtribe_back.{$ext}";
                                        }
                                    }

                                    // Si se detectó imagen frontal, generar candidatos derivados del nombre frontal
                                    if (!empty($finalImg)) {
                                        $frontNoExt = preg_replace('/\.(jpg|jpeg|png)$/i', '', $finalImg);
                                        foreach (['jpg','jpeg','png'] as $ext) {
                                            // mismo directorio que la frontal
                                            $backCandidates[] = $frontNoExt . '_back.' . $ext;
                                            // pruebas alternando guion/underscore
                                            $backCandidates[] = str_replace('_', '-', $frontNoExt) . '-back.' . $ext;
                                            $backCandidates[] = str_replace('-', '_', $frontNoExt) . '_back.' . $ext;
                                            // Si la frontal termina en '_frontal', probar el equivalente '_back'
                                            $backCandidates[] = preg_replace('/(_|-)frontal$/i', '$1back', $frontNoExt) . '.' . $ext;
                                            // variante español
                                            $backCandidates[] = $frontNoExt . '_trasera.' . $ext;
                                            // variante color negro desde la frontal
                                            $backCandidates[] = $frontNoExt . '_black.' . $ext;
                                            $backCandidates[] = str_replace('_', '-', $frontNoExt) . '-black.' . $ext;
                                            $backCandidates[] = str_replace('-', '_', $frontNoExt) . '_black.' . $ext;
                                        }
                                    }
                                    
                                    foreach ($backCandidates as $rel) {
                                        if ($existsRel($rel)) { $backImg = $rel; break; }
                                    }
                                ?>
                                <?php if (!empty($finalImg)) : ?>
                                  <img src="/<?php echo htmlspecialchars(ltrim($finalImg, '/')); ?>" alt="<?php echo htmlspecialchars($p['name']); ?> frontal" class="product-image front" style="width:100%; height:100%;" />
                                  <?php if (!empty($backImg)) : ?>
                                    <img src="/<?php echo htmlspecialchars(ltrim($backImg, '/')); ?>" alt="<?php echo htmlspecialchars($p['name']); ?> trasera" class="product-image back" style="width:100%; height:100%;" />
                                  <?php endif; ?>
                                <?php else : ?>
                                  <div class="d-flex align-items-center justify-content-center w-100 h-100 text-muted" style="background: #f7f7f7; border-radius: 12px;">
                                    <div class="text-center">
                                      <i class="fas fa-image fa-2x mb-2 opacity-50"></i>
                                      <div>Sin imagen</div>
                                    </div>
                                  </div>
                                <?php endif; ?>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                  const filterForms = Array.from(document.querySelectorAll('.filters-form'));
                  const sortingForm = document.querySelector('.sorting-controls');
                  const orderSelect = sortingForm ? sortingForm.querySelector('select[name="order"]') : null;

                  if (orderSelect && sortingForm) {
                    orderSelect.addEventListener('change', () => {
                      sortingForm.submit();
                    });
                  }

                  function attachFilterHandlers(form) {
                    const catHidden = form.querySelector('#filter-category-hidden');
                    const ropaChecks = Array.from(form.querySelectorAll('input[name="type[]"][data-group="textil"]'));
                    const accChecks = Array.from(form.querySelectorAll('input[name="type[]"][data-group="accesorios"]'));
                    function updateCategory() {
                      const anyRopa = ropaChecks.some(c => c.checked);
                      const anyAcc = accChecks.some(c => c.checked);
                      if (anyRopa && anyAcc) {
                        catHidden.value = '';
                      } else if (anyRopa) {
                        catHidden.value = 'textil';
                      } else if (anyAcc) {
                        catHidden.value = 'accesorios';
                      } else {
                        catHidden.value = '';
                      }
                    }
                    // Ajustar categoría desde el estado inicial para evitar inconsistencias
                    if (catHidden) updateCategory();
                    [...ropaChecks, ...accChecks].forEach(chk => {
                      chk.addEventListener('change', () => {
                        updateCategory();
                        form.submit();
                      });
                    });
                    form.addEventListener('submit', () => {
                      updateCategory();
                      if (!catHidden.value) { catHidden.removeAttribute('name'); }
                    });
                    const bindToggle = (hdr) => {
                      const body = hdr.nextElementSibling;
                      if (!body) return;
                      const toggle = () => {
                        const isOpen = body.classList.toggle('open');
                        hdr.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                        const chev = hdr.querySelector('.chevron');
                        if (chev) chev.classList.toggle('rotate', isOpen);
                      };
                      hdr.addEventListener('click', toggle);
                      hdr.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
                      });
                    };
                    form.querySelectorAll('.filter-header').forEach(bindToggle);
                  }
                  filterForms.forEach(attachFilterHandlers);

                  // Fallback defensivo: si por alguna razón no se adjuntaron manejadores dentro del formulario,
                  // enlazar directamente a encabezados visibles en sidebar y overlay móvil.
                  const extraHeaders = Array.from(document.querySelectorAll('.filters-sidebar .filter-header, #filtersOverlay .filter-header'));
                  extraHeaders.forEach(h => {
                    // Evitar duplicar manejadores si ya existe atributo de control
                    if (!h.__boundToggle) {
                      const body = h.nextElementSibling;
                      if (!body) return;
                      const toggle = () => {
                        const isOpen = body.classList.toggle('open');
                        h.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                        const chev = h.querySelector('.chevron');
                        if (chev) chev.classList.toggle('rotate', isOpen);
                      };
                      h.addEventListener('click', toggle);
                      h.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
                      });
                      h.__boundToggle = true;
                    }
                  });

                  // Overlays móviles
                  const filtersOverlay = document.getElementById('filtersOverlay');
                  const sortOverlay = document.getElementById('sortOverlay');
                  const openFiltersBtn = document.getElementById('openFiltersBtn');
                  const openSortBtn = document.getElementById('openSortBtn');
                  function openOverlay(el){ if (el) el.setAttribute('aria-hidden','false'); }
                  function closeOverlay(el){ if (el) el.setAttribute('aria-hidden','true'); }
                  if (openFiltersBtn) openFiltersBtn.addEventListener('click', () => openOverlay(filtersOverlay));
                  if (openSortBtn) openSortBtn.addEventListener('click', () => openOverlay(sortOverlay));
                  document.querySelectorAll('.overlay .overlay-close').forEach(btn => {
                    btn.addEventListener('click', () => closeOverlay(btn.closest('.overlay')));
                  });
                  document.querySelectorAll('.overlay').forEach(ov => {
                    ov.addEventListener('click', (e) => {
                      if (e.target === ov) closeOverlay(ov);
                    });
                  });
                  document.querySelectorAll('#sortOverlay .sort-option').forEach(btn => {
                    btn.addEventListener('click', () => {
                      const val = btn.getAttribute('data-order');
                      if (orderSelect) { orderSelect.value = val; }
                      if (sortingForm) { sortingForm.submit(); }
                    });
                  });
                });
                </script>
                                
                                <div class="position-absolute top-0 start-0 p-2 d-flex gap-2" style="z-index: 20;">
                                    <?php 
                                        $slugRaw = isset($p['slug']) ? (string)$p['slug'] : '';
                                        $slugNorm = str_replace('-', '_', strtolower($slugRaw));
                                    ?>
                                    <?php if ($slugNorm === 'camiseta_oficial_carrera') : ?>
                                        <span class="badge" style="background:#FFE08A; color:#3A3A3A; font-weight:700; border-radius:999px; padding:6px 10px;">Edición especial limitada</span>
                                    <?php elseif (!empty($p['is_offer'])) : ?>
                                        <span class="badge bg-danger">Oferta</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title mb-2 text-center" style="min-height: 44px;">
                                    <?php echo htmlspecialchars($p['name']); ?>
                                </h5>
                                <div class="mb-3 text-center">
                                    <?php 
                                        $slugRaw = isset($p['slug']) ? (string)$p['slug'] : '';
                                        $slugNorm = str_replace('-', '_', strtolower($slugRaw));
                                    ?>
                                    <?php if ($slugNorm === 'camiseta_oficial_carrera') : ?>
                                        <span class="h6 fw-bold">$<?php echo number_format(65000, 0, ',', '.'); ?></span>
                                    <?php else : ?>
                                        <span class="h6 fw-bold">$<?php echo number_format((float)$p['price'], 0, ',', '.'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php
                $page = isset($pagination['page']) ? (int)$pagination['page'] : 1;
                $pages = isset($pagination['pages']) ? (int)$pagination['pages'] : 1;

                // Construir base de query string manteniendo filtros y orden
                $qs = $_GET;
                unset($qs['page']);
                $baseQuery = http_build_query($qs);
            ?>

            <nav class="mt-4" aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo $baseQuery; ?>&page=<?php echo max(1, $page - 1); ?>" tabindex="-1">Anterior</a>
                    </li>
                    <?php for ($i = 1; $i <= $pages; $i++) : ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo $baseQuery; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo $baseQuery; ?>&page=<?php echo min($pages, $page + 1); ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>

        <?php else : ?>
            <div class="text-center py-5">
                <h3 class="fw-bold mb-3">Aún no hay productos disponibles</h3>
                <p class="text-muted mb-4">Muy pronto encontrarás nuestras novedades aquí.</p>
            </div>
        <?php endif; ?>
            </div> <!-- /.products-content -->
        </div> <!-- /.catalog-layout -->
    </div>
    <style>
        /* Panel de compra profesional */
        .purchase-panel { border: 1px solid #e9ecef; background: #fcfcfd; border-radius: 10px; padding: 12px; }
        .purchase-panel .form-label { font-weight: 600; color: #333; }
        .btn-whatsapp { background: #25D366; border-color: #25D366; color: #fff; font-weight: 700; }
        .btn-whatsapp:hover { background: #1ebe5d; border-color: #1ebe5d; color: #fff; }
        .validation-msg { color: #C92A2A; }
        /* Ajuste de imagen para esqueleto: mostrar contorno completo */
        .product-card.esqueleto-card .product-image { object-fit: contain !important; }
        .product-card.esqueleto-card .image-box { background: #fff; }
        .product-card.esqueleto-card .image-wrapper { padding: 8px; }
    </style>
    <script>
      // No hay controles de compra en tarjetas; sólo navegación al detalle.
      document.addEventListener('DOMContentLoaded', function() {
        const isCoarse = window.matchMedia('(hover: none)').matches || window.matchMedia('(pointer: coarse)').matches;
        if (!isCoarse) return;

        // En dispositivos táctiles: primer tap hace flip, segundo tap navega
        const cards = Array.from(document.querySelectorAll('.product-card'));
        cards.forEach(card => {
          const imageBox = card.querySelector('.image-box');
          const backImg = card.querySelector('.product-image.back');
          if (!imageBox || !backImg) return; // sin imagen trasera, no aplica flip

          card.addEventListener('click', function(e) {
            const flipped = imageBox.classList.contains('tap-flip');
            if (!flipped) {
              imageBox.classList.add('tap-flip');
              // Evitar navegación en el primer tap
              e.preventDefault();
              // Opcional: quitar flip si el usuario no navega en ~3s
              setTimeout(() => {
                imageBox.classList.remove('tap-flip');
              }, 3000);
            } else {
              // Ya está flipped: permitir navegación y restaurar estado visual
              imageBox.classList.remove('tap-flip');
            }
          }, true);
        });
      });
    </script>
</section>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>