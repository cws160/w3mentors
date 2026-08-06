import fs from 'node:fs';

const files = [
  'database/sample.sql',
  'database/blank.sql',
  'public/cache/en.json',
  'public/cache/ar.json',
];

const replacements = [
  ['w3mentors', 'w3mentors'],
  ['W3Mentors', 'w3mentors'],
  ['W3Mentors', 'w3mentors'],
  ['W3 Mentors', 'w3mentors'],
];

// Restore label keys that must keep legacy identifiers.
const keyFixes = [
  ['appSignInTow3mentors', 'appSignInToW3Mentors'],
];

const sitename = [
  ["('CONF_WEBSITE_NAME_1', 'Sitename', 0)", "('CONF_WEBSITE_NAME_1', 'w3mentors', 0)"],
  ["('CONF_WEBSITE_NAME_2', 'Sitename', 0)", "('CONF_WEBSITE_NAME_2', 'w3mentors', 0)"],
  ["('CONF_WEBSITE_NAME_3', 'Sitename', 0)", "('CONF_WEBSITE_NAME_3', 'w3mentors', 0)"],
  ["('CONF_FROM_NAME_1', 'Sitename', 0)", "('CONF_FROM_NAME_1', 'w3mentors', 0)"],
  ["('CONF_FROM_NAME_2', 'Sitename', 0)", "('CONF_FROM_NAME_2', 'w3mentors', 0)"],
  ["('CONF_FROM_NAME_3', 'Sitename', 0)", "('CONF_FROM_NAME_3', 'w3mentors', 0)"],
];

const root = new URL('..', import.meta.url).pathname.replace(/^\/([A-Za-z]:)/, '$1');

for (const rel of files) {
  const file = `${root}/${rel}`.replace(/\//g, '\\');
  if (!fs.existsSync(file)) {
    console.log('missing', file);
    continue;
  }
  let text = fs.readFileSync(file, 'utf8');
  for (const [old, neu] of replacements) {
    text = text.split(old).join(neu);
  }
  for (const [old, neu] of keyFixes) {
    text = text.split(old).join(neu);
  }
  if (file.endsWith('.sql')) {
    for (const [old, neu] of sitename) {
      text = text.split(old).join(neu);
    }
  }
  fs.writeFileSync(file, text, 'utf8');
  console.log('updated', file);
}
