/**
 * PDF Form Field Reader
 * ---------------------
 * Called by PHP via: node read-pdf-fields.js <templatePath>
 *
 * Returns JSON array of field definitions:
 * [{ name, type, options?, value? }]
 */
const { PDFDocument } = require('pdf-lib');
const fs = require('fs');

(async () => {
  const [,, templatePath] = process.argv;

  if (!templatePath) {
    console.error(JSON.stringify({ success: false, error: 'Usage: node read-pdf-fields.js <templatePath>' }));
    process.exit(1);
  }

  try {
    const templateBytes = fs.readFileSync(templatePath);
    const pdfDoc = await PDFDocument.load(templateBytes);
    const form = pdfDoc.getForm();
    const fields = form.getFields();

    const result = fields.map((field) => {
      const type = field.constructor.name;
      const entry = {
        name: field.getName(),
        type: type.replace('PDF', '').toLowerCase(), // textfield, checkbox, dropdown, radiogroup
      };

      if (type === 'PDFTextField') {
        try { entry.value = field.getText() || ''; } catch (e) { entry.value = ''; }
      }
      if (type === 'PDFCheckBox') {
        try { entry.value = field.isChecked(); } catch (e) { entry.value = false; }
      }
      if (type === 'PDFDropdown' || type === 'PDFOptionList') {
        try { entry.options = field.getOptions(); } catch (e) { entry.options = []; }
      }

      return entry;
    });

    console.log(JSON.stringify({ success: true, fields: result }));
  } catch (err) {
    console.error(JSON.stringify({ success: false, error: err.message }));
    process.exit(1);
  }
})();
