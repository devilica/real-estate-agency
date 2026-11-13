(function () {
  var toggle = document.querySelector('.nav-toggle');
  var nav = document.getElementById('site-nav') || document.getElementById('admin-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  document.querySelectorAll('[data-pass-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
      var input = button.parentElement.querySelector('input');
      if (!input) {
        return;
      }
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      button.setAttribute('aria-pressed', show ? 'true' : 'false');
      button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      var eye = button.querySelector('[data-eye]');
      var hiddenEye = button.querySelector('[data-eye-off]');
      if (eye && hiddenEye) {
        eye.hidden = show;
        hiddenEye.hidden = !show;
      }
      input.focus();
    });
  });

  var filters = document.querySelector('[data-filters]');
  if (filters && filters.getAttribute('data-active') === '0' && window.matchMedia('(max-width: 900px)').matches) {
    filters.open = false;
  }
  if (filters) {
    filters.querySelector('summary').addEventListener('click', function (event) {
      if (window.matchMedia('(min-width: 901px)').matches) {
        event.preventDefault();
      }
    });
  }

  document.querySelectorAll('form[data-auto-submit]').forEach(function (form) {
    form.addEventListener('change', function () {
      var params = new URLSearchParams();
      new FormData(form).forEach(function (value, key) {
        if (value !== '') {
          params.append(key, value);
        }
      });
      var query = params.toString();
      window.location.href = form.action + (query ? '?' + query : '');
    });
  });

  var tipSelect = document.querySelector('select[name="tip"]');
  var podtipSelect = document.querySelector('select[name="podtip"]');
  if (tipSelect && podtipSelect) {
    var syncPodtip = function () {
      var tip = tipSelect.value;
      var current = podtipSelect.value;
      var keep = false;
      Array.prototype.forEach.call(podtipSelect.options, function (option) {
        if (option.value === '') {
          option.hidden = false;
          option.disabled = false;
          return;
        }
        var match = option.getAttribute('data-tip') === tip;
        option.hidden = !match;
        option.disabled = !match;
        if (match && option.value === current) {
          keep = true;
        }
      });
      podtipSelect.value = keep ? current : '';
    };
    tipSelect.addEventListener('change', syncPodtip);
    syncPodtip();
  }

  document.querySelectorAll('[data-gallery]').forEach(function (gallery) {
    var main = gallery.querySelector('.gallery-main');
    var thumbs = Array.prototype.slice.call(gallery.querySelectorAll('[data-src]'));
    var indexEl = gallery.querySelector('[data-gal-index]');
    if (!main || thumbs.length < 2) {
      return;
    }
    var current = 0;

    function show(i) {
      current = (i + thumbs.length) % thumbs.length;
      var src = thumbs[current].getAttribute('data-src');
      if (main.getAttribute('src') !== src) {
        main.classList.add('is-loading');
        main.onload = function () {
          main.classList.remove('is-loading');
        };
        main.src = src;
      }
      thumbs.forEach(function (item, n) {
        item.classList.toggle('is-current', n === current);
      });
      if (indexEl) {
        indexEl.textContent = String(current + 1);
      }
      thumbs[current].scrollIntoView({ block: 'nearest', inline: 'nearest' });
    }

    thumbs.forEach(function (button, n) {
      button.addEventListener('click', function () {
        show(n);
      });
    });
    gallery.querySelectorAll('[data-gal]').forEach(function (button) {
      button.addEventListener('click', function () {
        show(current + (button.getAttribute('data-gal') === 'prev' ? -1 : 1));
      });
    });
    document.addEventListener('keydown', function (event) {
      var tag = (event.target && event.target.tagName) || '';
      if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
        return;
      }
      if (event.key === 'ArrowLeft') {
        show(current - 1);
      } else if (event.key === 'ArrowRight') {
        show(current + 1);
      }
    });

    var startX = null;
    main.parentElement.addEventListener('touchstart', function (event) {
      startX = event.touches[0].clientX;
    }, { passive: true });
    main.parentElement.addEventListener('touchend', function (event) {
      if (startX === null) {
        return;
      }
      var dx = event.changedTouches[0].clientX - startX;
      startX = null;
      if (Math.abs(dx) > 40) {
        show(current + (dx < 0 ? 1 : -1));
      }
    });
  });

  var upload = document.querySelector('[data-upload]');
  var uploadPreview = document.querySelector('[data-upload-preview]');
  var uploadInfo = document.querySelector('[data-upload-info]');
  var canRebuild = false;
  try {
    canRebuild = typeof DataTransfer === 'function' && !!new DataTransfer().items;
  } catch (e) {
    canRebuild = false;
  }
  if (upload && uploadPreview && uploadInfo && canRebuild && window.URL && URL.createObjectURL) {
    var MAX_IMAGES = 12;
    var MAX_BYTES = 8 * 1024 * 1024;
    var selectedFiles = [];
    var previewUrls = [];

    var existingCount = function () {
      var total = parseInt(upload.getAttribute('data-existing') || '0', 10) || 0;
      var removed = document.querySelectorAll('input[name="obrisi[]"]:checked').length;
      return Math.max(0, total - removed);
    };

    var fileKey = function (file) {
      return file.name + '|' + file.size + '|' + file.lastModified;
    };

    var plural = function (n) {
      return n + (n === 1 ? ' new image' : ' new images');
    };

    var removeIcon = function () {
      var ns = 'http://www.w3.org/2000/svg';
      var svg = document.createElementNS(ns, 'svg');
      svg.setAttribute('viewBox', '0 0 24 24');
      svg.setAttribute('aria-hidden', 'true');
      svg.setAttribute('class', 'icon');
      var path = document.createElementNS(ns, 'path');
      path.setAttribute('d', 'M6 6l12 12M18 6 6 18');
      svg.appendChild(path);
      return svg;
    };

    var syncInput = function () {
      var transfer = new DataTransfer();
      selectedFiles.forEach(function (file) {
        transfer.items.add(file);
      });
      upload.files = transfer.files;
    };

    var renderPreview = function () {
      previewUrls.forEach(function (src) {
        URL.revokeObjectURL(src);
      });
      previewUrls = [];
      uploadPreview.textContent = '';

      var tooBig = 0;
      selectedFiles.forEach(function (file, n) {
        var li = document.createElement('li');
        var src = URL.createObjectURL(file);
        previewUrls.push(src);
        var img = document.createElement('img');
        img.src = src;
        img.alt = '';
        li.appendChild(img);
        if (file.size > MAX_BYTES) {
          li.classList.add('is-invalid');
          tooBig++;
        }
        var name = document.createElement('span');
        name.className = 'upload-name';
        name.textContent = file.name;
        li.appendChild(name);
        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'upload-remove';
        remove.setAttribute('aria-label', 'Remove image ' + file.name);
        remove.appendChild(removeIcon());
        remove.addEventListener('click', function () {
          selectedFiles.splice(n, 1);
          syncInput();
          renderPreview();
          upload.focus();
        });
        li.appendChild(remove);
        uploadPreview.appendChild(li);
      });

      uploadPreview.hidden = selectedFiles.length === 0;
      var messages = [];
      var warn = false;
      if (selectedFiles.length > 0) {
        messages.push('Selected: ' + plural(selectedFiles.length) + '.');
      }
      var total = existingCount() + selectedFiles.length;
      if (selectedFiles.length > 0 && total > MAX_IMAGES) {
        messages.push('A listing can have at most 12 images, and this selection makes ' + total + '. Remove ' + (total - MAX_IMAGES) + '.');
        warn = true;
      }
      if (tooBig > 0) {
        messages.push('The marked images are larger than 8 MB and will not be accepted.');
        warn = true;
      }
      uploadInfo.textContent = messages.join(' ');
      uploadInfo.hidden = messages.length === 0;
      uploadInfo.classList.toggle('is-warning', warn);
    };

    upload.addEventListener('change', function () {
      var known = selectedFiles.map(fileKey);
      Array.prototype.forEach.call(upload.files, function (file) {
        if (known.indexOf(fileKey(file)) === -1) {
          selectedFiles.push(file);
          known.push(fileKey(file));
        }
      });
      syncInput();
      renderPreview();
    });

    document.querySelectorAll('input[name="obrisi[]"]').forEach(function (box) {
      box.addEventListener('change', renderPreview);
    });
  }

  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      var message = form.getAttribute('data-confirm') || 'Nastaviti?';
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });

  var list = document.querySelector('[data-sortable]');
  var bar = document.querySelector('[data-sort-bar]');
  var sortForm = document.querySelector('[data-sort-form]');
  if (list && bar && sortForm) {
    var initial = ids().join(',');
    var dragging = null;
    var pointerId = null;

    function ids() {
      return Array.prototype.map.call(list.querySelectorAll('.admin-item'), function (item) {
        return item.getAttribute('data-id');
      });
    }

    function refresh() {
      var items = list.querySelectorAll('.admin-item');
      Array.prototype.forEach.call(items, function (item, n) {
        var num = item.querySelector('[data-order-num]');
        if (num) {
          num.textContent = String(n + 1);
        }
        item.querySelectorAll('.move-btn').forEach(function (btn, k) {
          btn.disabled = k === 0 ? n === 0 : n === items.length - 1;
        });
      });
      var changed = ids().join(',') !== initial;
      bar.hidden = !changed;
      list.querySelectorAll('.move-form button').forEach(function (btn) {
        if (changed) {
          btn.disabled = true;
        }
      });
    }

    function clearMarks() {
      list.querySelectorAll('.drop-before, .drop-after').forEach(function (el) {
        el.classList.remove('drop-before', 'drop-after');
      });
    }

    function onMove(event) {
      if (!dragging || event.pointerId !== pointerId) {
        return;
      }
      event.preventDefault();
      var y = event.clientY;
      var items = Array.prototype.slice.call(list.querySelectorAll('.admin-item'));
      var target = null;
      for (var i = 0; i < items.length; i++) {
        var item = items[i];
        if (item === dragging) {
          continue;
        }
        var rect = item.getBoundingClientRect();
        if (y < rect.top + rect.height / 2) {
          target = item;
          break;
        }
      }
      if (target) {
        if (target !== dragging.nextElementSibling) {
          list.insertBefore(dragging, target);
        }
      } else if (list.lastElementChild !== dragging) {
        list.appendChild(dragging);
      }
      var margin = 80;
      if (y < margin) {
        window.scrollBy(0, -12);
      } else if (y > window.innerHeight - margin) {
        window.scrollBy(0, 12);
      }
      refresh();
    }

    function onUp(event) {
      if (!dragging || event.pointerId !== pointerId) {
        return;
      }
      dragging.classList.remove('is-dragging');
      dragging = null;
      pointerId = null;
      clearMarks();
      document.removeEventListener('pointermove', onMove);
      document.removeEventListener('pointerup', onUp);
      document.removeEventListener('pointercancel', onUp);
      refresh();
    }

    list.querySelectorAll('[data-drag]').forEach(function (handle) {
      handle.addEventListener('pointerdown', function (event) {
        if (event.button !== undefined && event.button !== 0) {
          return;
        }
        event.preventDefault();
        dragging = handle.closest('.admin-item');
        pointerId = event.pointerId;
        dragging.classList.add('is-dragging');
        document.addEventListener('pointermove', onMove, { passive: false });
        document.addEventListener('pointerup', onUp);
        document.addEventListener('pointercancel', onUp);
      });

      handle.addEventListener('keydown', function (event) {
        var item = handle.closest('.admin-item');
        if (event.key === 'ArrowUp' && item.previousElementSibling) {
          event.preventDefault();
          list.insertBefore(item, item.previousElementSibling);
          handle.focus();
          refresh();
        } else if (event.key === 'ArrowDown' && item.nextElementSibling) {
          event.preventDefault();
          list.insertBefore(item.nextElementSibling, item);
          handle.focus();
          refresh();
        }
      });
    });

    sortForm.addEventListener('submit', function () {
      sortForm.querySelectorAll('input[name="redoslijed[]"]').forEach(function (input) {
        input.remove();
      });
      ids().forEach(function (id) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'redoslijed[]';
        input.value = id;
        sortForm.appendChild(input);
      });
    });

    window.addEventListener('beforeunload', function (event) {
      if (!bar.hidden && !sortForm.dataset.submitting) {
        event.preventDefault();
        event.returnValue = '';
      }
    });
    sortForm.addEventListener('submit', function () {
      sortForm.dataset.submitting = '1';
    });
    sortForm.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        sortForm.dataset.submitting = '1';
      });
    });
  }
})();
