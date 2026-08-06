/** Parse legacy `?tag=name-id` query (e.g. english-5). */
export function parseForumTagParam(tag: string | null): { tagId: number; name: string } | null {
  if (!tag) return null;
  const parts = tag.split('-');
  if (parts.length < 2) return null;
  const id = Number(parts[parts.length - 1]);
  if (!Number.isFinite(id) || id < 1) return null;
  return { tagId: id, name: parts.slice(0, -1).join('-') };
}

export function forumTagHref(tag: { id: number; name: string }): string {
  return `/forum?tag=${encodeURIComponent(`${tag.name}-${tag.id}`)}`;
}
