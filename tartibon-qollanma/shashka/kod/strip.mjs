import {stripTypeScriptTypes} from 'node:module';
import {readFileSync,writeFileSync} from 'node:fs';
const [src,out]=process.argv.slice(2);
const js=stripTypeScriptTypes(readFileSync(src,'utf8'),{mode:'strip'});
writeFileSync(out,js);
console.log(src.split('/').pop(),'->',out.split('/').pop(),js.length,'bayt');
