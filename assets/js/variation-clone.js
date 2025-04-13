jQuery(document).ready(function ($) {
  // Βοηθητική συνάρτηση: κανονικοποιεί string (για σύγκριση)
  const normalize = str => str.trim().toLowerCase();
  
  // Αναμονή μέχρι να εμφανιστεί ένα DOM στοιχείο (με retry loop)
  const waitForElement = (selector, callback, timeout = 10000) => {
    const startTime = Date.now();
    const interval = setInterval(() => {
      const element = $(selector);
      if (element.length) {
        clearInterval(interval);
        callback(element);
      } else if (Date.now() - startTime > timeout) {
        clearInterval(interval);
        console.warn(`⚠️ Element '${selector}' not found in time`);
      }
    }, 300);
  };

  const attributesPerVariation = {};
  // Αποθηκεύει τα διαθέσιμα attributes από το DOM (π.χ. color, size)
  function parseAttributesFromDOM() {
    const attributes = {};
    $("input[name^='attribute_names']").each(function (index) {
      const taxonomy = $(this).val();
      const select = $(`select[name='attribute_values[${index}][]']`);
      const options = [];
      select.find("option").each(function () {
        const val = $(this).val();
        const text = $(this).text().trim();
        if (val) {
          options.push({ slug: val.trim(), name: text.toLowerCase() });
        }
      });
      attributes[taxonomy] = options;
    });
    return attributes;
  }

  // Επιλέγει αυτόματα τον κατάλληλο όρο στο dropdown (π.χ. Red)
  function assignTermToSelect(select, targetSlug) {
    let matched = false;
    select.find("option").each(function () {
      const option = $(this);
      if (normalize(option.val()) === normalize(targetSlug)) {
        option.prop("selected", true);
        matched = true;
      }
    });
    if (matched) {
      select.trigger("change").trigger("blur");
    }
  }

  // Συμπληρώνει τιμές πεδίων παραλλαγής (τιμές, stock, εικόνα, κ.λπ.)
  function fillVariationFields(row, index, data) {
    const findInput = (name) => row.find(`input[name='${name}[${index}]']`);
    if (row.find(`input[name='variable_post_id[${index}]']`).length === 0) {
      row.append(`<input type="hidden" name="variable_post_id[${index}]" value="0">`);
    }

    findInput("variable_regular_price").val(data.regular_price);
    findInput("variable_sale_price").val(data.sale_price);
    findInput("variable_sku").val(data.sku);
    if (data.manage_stock) {
      findInput("variable_manage_stock").prop("checked", true).trigger("change");
      setTimeout(() => {
        findInput("variable_stock").val(data.stock_qty);
      }, 300);
    }
    findInput("variable_weight").val(data.weight);
    findInput("variable_length").val(data.length);
    findInput("variable_width").val(data.width);
    findInput("variable_height").val(data.height);
    if (data.enabled === false) {
      findInput("variable_enabled").prop("checked", false);
    }

    // Ρυθμίζει την εικόνα της παραλλαγής
	const imgInput = row.find(`input[name='upload_image_id[${index}]']`);
    const imgTag = row.find("a.upload_image_button img");
    if (!data.image || !data.image.id || !data.image.url) {
      imgInput.val("");
      imgTag.attr("src", "");
    } else {
      imgInput.val(data.image.id);
      imgTag.attr("src", data.image.url);
    }
  }

  // Αντιστοιχεί τα attributes DOM στις αντίστοιχες τιμές παραλλαγής
  function assignAttributesToVariation(row, index, data, attributesDOM) {
    if (Array.isArray(data.attributes)) {
      data.attributes.forEach(({ taxonomy, slug: pSlug, name }) => {
        const domOptions = attributesDOM[taxonomy] || [];
        const select = row.find(`select[name='attribute_${taxonomy}[${index}]']`);
        if (select.length && domOptions.length > 0) {
          domOptions.forEach(({ name: domName, slug }) => {
            if (normalize(domName) === normalize(name)) {
              assignTermToSelect(select, pSlug);
            }
          });
        }
      });
    }
  }

  // Αναδιάταξη παραλλαγών στον πίνακα του WooCommerce ανά ID
  function reorderVariationsById() {
    const container = $("#variable_product_options .woocommerce_variations");
    const variations = container.children(".woocommerce_variation").get();
    variations.sort((a, b) => {
      const idA = parseInt($(a).find("input[name^='variable_post_id']").val(), 10);
      const idB = parseInt($(b).find("input[name^='variable_post_id']").val(), 10);
      return idA - idB;
    });
    variations.forEach(el => container.append(el));
  }

  // Δημιουργία και συμπλήρωση παραλλαγών από AJAX δεδομένα
  function createAndFillVariations(variations, attributesDOM) {
    let currentIndex = 0;
    const total = variations.length;

    // Loader για εμφάνιση κατά τη δημιουργία
	const loader = $(`
      <div id="bp-variation-loader" style="
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(255,255,255,0.9);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #0073aa;
        font-weight: bold;
        font-family: sans-serif;
        text-align: center;
      ">
        <div id="bp-variation-loader-text">Create Variation: 1/${total}</div>
      </div>
    `);
    loader.hide();
    $("body").append(loader);
    loader.fadeIn(400);

    // Ενημέρωση του loader με την τρέχουσα παραλλαγή
	function updateLoader() {
      $("#bp-variation-loader-text").text(`Create Variation: ${currentIndex + 1}/${total}`);
    }

    // Επαναληπτική δημιουργία παραλλαγών μία-μία
	function processNext() {
      if (currentIndex >= total) {
        $("#bp-variation-loader").fadeOut(400, function () {
          $(this).remove();
        });
        console.log("🎉 Όλες οι παραλλαγές δημιουργήθηκαν!");
        reorderVariationsById();
        return;
      }

      updateLoader();

      const data = variations[currentIndex];
      const addButton = $(".add_variation_manually");
      if (!addButton.length) return;
      addButton.trigger("click");

      waitForElement(`input[name='variable_post_id[${currentIndex}]']`, () => {
        const row = $(`input[name='variable_post_id[${currentIndex}]']`).closest(".woocommerce_variation");
        setTimeout(() => {
          fillVariationFields(row, currentIndex, data);
          assignAttributesToVariation(row, currentIndex, data, attributesDOM);

          // AJAX call για mapping original → νέα παραλλαγή (για bidirectional sync)
          const newVarId = row.find("input[name^='variable_post_id']").val();
          $.post(ajaxurl, {
            action: 'bp_wc_mark_cloned_variation',
            nonce: bp_wc_variation_clone.nonce,
            new_id: newVarId,
            original_id: data.id
          }, function (res) {
            if (res.success) {
              console.log(`✅ Mapping: ${data.id} → ${newVarId}`);
            } else {
              console.warn(`⚠️ Mapping failed for ${data.id}`);
            }
          });

          currentIndex++;
          setTimeout(processNext, 600);
        }, 600);
      });
    }

    processNext();
  }

  // Ξεκινάει η διαδικασία κλωνοποίησης παραλλαγών από original προϊόν
  function startCloning() {
    console.log("🚀 Κλωνοποίηση παραλλαγών ξεκίνησε...");
    const attributesDOM = parseAttributesFromDOM();
    $.ajax({
      url: ajaxurl,
      method: "POST",
      dataType: "json",
      data: {
        action: "bp_wc_clone_variations",
        nonce: bp_wc_variation_clone.nonce,
        original_product_id: bp_wc_variation_clone.original_product_id,
      },
      success: function (res) {
        if (res.success && res.data.variations.length > 0) {
          console.log(`📦 Λήφθηκαν ${res.data.variations.length} παραλλαγές`);
          createAndFillVariations(res.data.variations, attributesDOM);
        }
      },
      error: function () {
        console.error("❌ Σφάλμα AJAX κατά την ανάκτηση παραλλαγών");
      },
    });
  }

  // Περιμένουμε να επιλεγεί τύπος προϊόντος "variable" και ξεκινάμε cloning
  waitForElement("select#product-type", () => {
    const typeSelect = $("select#product-type");
    typeSelect.on("change", function () {
      if ($(this).val() === "variable") {
        setTimeout(startCloning, 1200);
      }
    });
    if (typeSelect.val() === "variable") {
      setTimeout(startCloning, 1200);
    }

    // Οπτική αναδιάταξη παραλλαγών
	setTimeout(() => {
      reorderVariationsById();
    }, 1500);
  });
});