const fs = require('fs')
const s = fs.readFileSync('../dist/chatify.css', 'utf8')
const re = /url\(([^)]+)\)/g
let m
const out = new Set()
while ((m = re.exec(s))) {
  const u = m[1].replace(/^['"]|['"]$/g, '')
  if (/vendor|chatify|patterns|fonts/.test(u)) out.add(u)
}
console.log([...out].join('\n'))
console.log('--- font faces ---')
const faces = s.match(/@font-face\{[^}]*\}/g) || []
for (const f of faces) console.log(f)
