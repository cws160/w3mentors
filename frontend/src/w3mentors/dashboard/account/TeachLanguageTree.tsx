import { useEffect, useMemo, useState } from 'react';

export type TeachLanguageNode = {
  id: number;
  name: string;
  subcategories: number;
  available: number;
  level: number;
  children: TeachLanguageNode[];
};

type Props = {
  nodes: TeachLanguageNode[];
  selectedIds: number[];
  onChange: (ids: number[]) => void;
};

function subtreeHasSelection(node: TeachLanguageNode, selectedIds: number[]): boolean {
  if (selectedIds.includes(node.id)) {
    return true;
  }
  return node.children.some((child) => subtreeHasSelection(child, selectedIds));
}

function collectOpenIds(nodes: TeachLanguageNode[], selectedIds: number[]): Set<number> {
  const open = new Set<number>();
  const walk = (list: TeachLanguageNode[]) => {
    for (const node of list) {
      if (node.children.length > 0) {
        if (node.children.some((c) => subtreeHasSelection(c, selectedIds))) {
          open.add(node.id);
        }
        walk(node.children);
      }
    }
  };
  walk(nodes);
  return open;
}

function TeachLanguageList({
  nodes,
  selectedIds,
  onToggle,
  openIds,
  onOpenToggle,
  isNested,
}: {
  nodes: TeachLanguageNode[];
  selectedIds: number[];
  onToggle: (id: number, checked: boolean) => void;
  openIds: Set<number>;
  onOpenToggle: (id: number) => void;
  isNested: boolean;
}) {
  const visible = nodes.filter((n) => n.available === 1);
  if (visible.length === 0) {
    return null;
  }

  const ulClass = isNested ? 'is-dropdown' : 'parentDropdownJs';

  return (
    <ul className={ulClass}>
      {visible.map((node) => {
        const hasChildren = node.children.length > 0;
        const isOpen = openIds.has(node.id);

        return (
          <li key={node.id} className={hasChildren ? 'is-child' : undefined}>
            {hasChildren ? (
              <>
                <span
                  className={`trigger accordion-header${isOpen ? ' is-active' : ''}`}
                  role="button"
                  tabIndex={0}
                  onClick={() => onOpenToggle(node.id)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                      e.preventDefault();
                      onOpenToggle(node.id);
                    }
                  }}
                >
                  {node.name}
                </span>
                {isOpen ? (
                  <TeachLanguageList
                    nodes={node.children}
                    selectedIds={selectedIds}
                    onToggle={onToggle}
                    openIds={openIds}
                    onOpenToggle={onOpenToggle}
                    isNested
                  />
                ) : null}
              </>
            ) : (
              <label className="accordion-trigger">
                <input
                  type="checkbox"
                  name="teach_lang_id[]"
                  value={node.id}
                  checked={selectedIds.includes(node.id)}
                  onChange={(e) => onToggle(node.id, e.target.checked)}
                />
                <span className="accordion-trigger-action">
                  <span className="accordion-trigger-label accordion-header">{node.name}</span>
                  <span className="accordion-trigger-icon" />
                </span>
              </label>
            )}
          </li>
        );
      })}
    </ul>
  );
}

export function TeachLanguageTree({ nodes, selectedIds, onChange }: Props) {
  const initialOpen = useMemo(() => collectOpenIds(nodes, selectedIds), [nodes, selectedIds]);
  const [openIds, setOpenIds] = useState<Set<number>>(initialOpen);

  useEffect(() => {
    setOpenIds((prev) => new Set([...prev, ...initialOpen]));
  }, [initialOpen]);

  const onToggle = (id: number, checked: boolean) => {
    onChange(checked ? [...selectedIds, id] : selectedIds.filter((x) => x !== id));
  };

  const onOpenToggle = (id: number) => {
    setOpenIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
      return next;
    });
  };

  return (
    <div className="multilevel-dropdown p-4 accordionJs">
      <TeachLanguageList
        nodes={nodes}
        selectedIds={selectedIds}
        onToggle={onToggle}
        openIds={openIds}
        onOpenToggle={onOpenToggle}
        isNested={false}
      />
    </div>
  );
}
