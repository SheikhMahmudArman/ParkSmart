import {cp,mkdir} from 'node:fs/promises';
import {fileURLToPath} from 'node:url';
const source=fileURLToPath(new URL('../dist/',import.meta.url));
const target=fileURLToPath(new URL('../../backend/parkbackend/public/app/',import.meta.url));
await mkdir(target,{recursive:true});
await cp(source,target,{recursive:true});
console.log('React build copied to Laravel public/app. Open http://127.0.0.1:8000');
