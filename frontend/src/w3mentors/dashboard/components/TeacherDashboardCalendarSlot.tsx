import { memo, useRef } from 'react';
import { useTeacherDashboardCalendar } from '../hooks/useTeacherDashboardCalendar';

type Props = {
  teacherId: number;
};

/**
 * Isolated calendar mount — must not re-render when parent dashboard state updates,
 * otherwise React reconciles an empty VDOM over FullCalendar's DOM and blanks the page.
 */
export const TeacherDashboardCalendarSlot = memo(
  function TeacherDashboardCalendarSlot({ teacherId }: Props) {
    const containerRef = useRef<HTMLDivElement>(null);
    useTeacherDashboardCalendar(containerRef, teacherId);
    return (
      <div
        ref={containerRef}
        id="d_calendar"
        className="dashboard-calendar calendar-view"
      />
    );
  },
  (prev, next) => prev.teacherId === next.teacherId
);
