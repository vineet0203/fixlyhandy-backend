/**
 * PDF Form Filler Worker
 * ---------------------
 * Called by PHP via: node fill-pdf.js <templatePath> <dataJsonPath> <outputPath>
 *
 * - Loads a fillable PDF template
 * - Reads field data from a JSON file
 * - Fills text fields and checkboxes
 * - Flattens the form (makes fields non-editable)
 * - Writes the filled PDF to outputPath
 */
const { PDFDocument } = require('pdf-lib');
const fs = require('fs');

(async () => {
  const [,, templatePath, dataJsonPath, outputPath] = process.argv;

  if (!templatePath || !dataJsonPath || !outputPath) {
    console.error(JSON.stringify({
      success: false,
      error: 'Usage: node fill-pdf.js <templatePath> <dataJsonPath> <outputPath>',
    }));
    process.exit(1);
  }

  try {
    // Read inputs
    const templateBytes = fs.readFileSync(templatePath);
    const fieldData = JSON.parse(fs.readFileSync(dataJsonPath, 'utf-8'));

    // Load PDF
    const pdfDoc = await PDFDocument.load(templateBytes);
    const form = pdfDoc.getForm();

    // Fill fields
    for (const [fieldName, value] of Object.entries(fieldData)) {
      try {
        const field = form.getField(fieldName);
        if (!field) continue;

        const type = field.constructor.name;

        if (type === 'PDFTextField') {
          field.setText(value != null ? String(value) : '');
        } else if (type === 'PDFCheckBox') {
          if (value === true || value === 'true' || value === '1' || value === 'Yes') {
            field.check();
          } else {
            field.uncheck();
          }
        } else if (type === 'PDFDropdown') {
          field.select(String(value));
        } else if (type === 'PDFRadioGroup') {
          field.select(String(value));
        }
      } catch (fieldErr) {
        // Log but don't fail — skip fields that can't be filled
        console.error(`Warning: Could not fill field "${fieldName}": ${fieldErr.message}`);
      }
    }

    // Flatten the form so fields become static text
    form.flatten();

    // Save
    const filledBytes = await pdfDoc.save();
    fs.writeFileSync(outputPath, filledBytes);

    console.log(JSON.stringify({
      success: true,
      output: outputPath,
      fieldsFilled: Object.keys(fieldData).length,
    }));
  } catch (err) {
    console.error(JSON.stringify({
      success: false,
      error: err.message,
    }));
    process.exit(1);
  }
})();
