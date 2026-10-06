const fs = require('node:fs/promises');
const { PDFDocument } = require('pdf-lib');

async function main() {
    const [source, destination] = process.argv.slice(2);
    if (!source || !destination) throw new Error('Source and destination are required.');
    const document = await PDFDocument.load(await fs.readFile(source), { updateMetadata: false });
    const bytes = await document.save({ useObjectStreams: false, updateFieldAppearances: false });
    await fs.writeFile(destination, bytes, { flag: 'wx' });
}

main().catch(error => {
    console.error(error.message);
    process.exitCode = 1;
});
