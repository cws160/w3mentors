import type { ReactNode } from 'react';
import { useSite } from '../../context/SiteContext';

type Props = {
  labelKey?: string;
  labelFallback?: string;
  /** Legacy `message-display` heading (e.g. LBL_NO_GROUP_CLASS). */
  headingKey?: string;
  headingFallback?: string;
  /** Legacy no-record-found.php optional CTA button */
  action?: ReactNode;
};

/** Legacy dashboard/views/_partial/no-record-found.php envelope illustration */
function NoRecordIcon() {
  return (
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 460.571 373.649" aria-hidden>
      <g transform="translate(-700 -2490)">
        <path
          fill="#ccd0d9"
          d="M343.893,118.759v22.694l-232.8-1.631s-.7,1.347-1.691-13.153v-42s-2.234-11.96,11.822-11.96h72.411l17.676,28.748,119.086,1.386s13.5,2.26,13.5,15.914"
          transform="translate(688.518 2438.915)"
        />
        <path
          fill="none"
          stroke="#b1b5c4"
          strokeWidth="4"
          strokeMiterlimit="10"
          d="M326.87,101.459H211.341L194.991,74.33a3.434,3.434,0,0,0-2.878-1.619H124.469c-5.39,0-9.523,1.619-12.038,4.761a13.312,13.312,0,0,0-2.7,10.152V257.415c0,6.2,1.709,10.78,5.3,13.925a15.764,15.764,0,0,0,10.421,3.593,10.868,10.868,0,0,0,1.889-.09h211.92a3.375,3.375,0,0,0,3.328-3.328V119.875C342.681,107.477,332.349,102.446,326.87,101.459Z"
          transform="translate(688.486 2438.915)"
        />
        <path
          fill="#b1b5c4"
          d="M394.855,358.35a1.151,1.151,0,0,0-1.627,1.627l3.446,3.447a1.123,1.123,0,0,0,.814.335.985.985,0,0,0,.813-.335,1.187,1.187,0,0,0,0-1.628Z"
          transform="translate(660.013 2410.24)"
        />
        <path
          fill="#b1b5c4"
          d="M405.619,354.513l-3.78-3.78a1.262,1.262,0,1,0-1.785,1.785l3.78,3.78a1.227,1.227,0,0,0,.892.368,1.154,1.154,0,0,0,.892-.368,1.3,1.3,0,0,0,0-1.785"
          transform="translate(659.33 2411.009)"
        />
        <path
          fill="#fff"
          stroke="#b1b5c4"
          strokeWidth="4"
          strokeMiterlimit="10"
          d="M305.525,163.257A100.833,100.833,0,1,0,406.358,264.09,100.787,100.787,0,0,0,305.525,163.257Z"
          transform="translate(678.928 2429.814)"
        />
        <path
          fill="#fff"
          stroke="#b1b5c4"
          strokeWidth="4"
          strokeMiterlimit="10"
          d="M307.49,182.811a83.244,83.244,0,1,0,83.244,83.244A83.207,83.207,0,0,0,307.49,182.811Z"
          transform="translate(676.963 2427.849)"
        />
        <path
          fill="#b1b5c4"
          d="M343.526,146.837a1.349,1.349,0,0,0-1.329-1.016H110.231a1.34,1.34,0,0,0-1.333,1.346c0,.033,0,.067,0,.1a1.387,1.387,0,0,0,1.446,1.25H342.237a1.367,1.367,0,0,0,1.094-.547,1.314,1.314,0,0,0,.2-1.133"
          transform="translate(688.556 2431.567)"
        />
        <path
          fill="none"
          stroke="#fff"
          strokeMiterlimit="10"
          d="M300.089,309.212s2.159-13.043,17.36-13.043c0,0,17.36,0,17.36,13.043"
          transform="translate(669.34 2416.456)"
        />
        <g transform="translate(942.292 2665.979)">
          <path
            fill="#b1b5c4"
            d="M279.5,251.373l4.009-4.009a1.781,1.781,0,0,0-2.492-2.544l-.026.026-4.008,4.008-4.009-4.008a1.781,1.781,0,0,0-2.544,2.492l.026.025,4.008,4.009-4.008,4.008a1.833,1.833,0,0,0,0,2.518,1.672,1.672,0,0,0,1.233.515,1.592,1.592,0,0,0,1.233-.515l4.009-4.008,4.008,4.008a1.592,1.592,0,0,0,1.233-.515,1.833,1.833,0,0,0,0-2.518Z"
            transform="translate(-269.92 -244.312)"
          />
          <path
            fill="#b1b5c4"
            d="M357.146,251.373l4.009-4.009a1.781,1.781,0,0,0-2.492-2.544l-.026.026-4.008,4.008-4.009-4.008a1.781,1.781,0,0,0-2.544,2.492l.026.025,4.008,4.009-4.008,4.008a1.833,1.833,0,0,0,0,2.518,1.674,1.674,0,0,0,1.233.515,1.592,1.592,0,0,0,1.233-.515l4.009-4.008,4.008,4.008a1.674,1.674,0,0,0,1.233.515,1.592,1.592,0,0,0,1.233-.515,1.833,1.833,0,0,0,0-2.518Z"
            transform="translate(-277.724 -244.312)"
          />
          <path
            fill="#b1b5c4"
            d="M330.513,304.263c-1.517-7.948-8.372-9.1-12.316-9.1-9.465,0-12.074,6.067-12.8,8.677a1.994,1.994,0,0,0,.425,1.82,1.973,1.973,0,0,0,1.638.729h.121a2.143,2.143,0,0,0,1.82-1.456c.607-2.063,2.488-5.521,8.8-5.521.789,0,7.584.182,8.312,5.521a2,2,0,0,0,2.063,1.759,2.263,2.263,0,0,0,1.578-.729,2.1,2.1,0,0,0,.363-1.7"
            transform="translate(-273.479 -249.422)"
          />
        </g>
        <path
          fill="#fff"
          stroke="#b1b5c4"
          strokeWidth="4"
          strokeMiterlimit="10"
          d="M464.146,422.193h0a17.644,17.644,0,0,1-24.953.053l-.053-.053-37.059-37.059a17.682,17.682,0,1,1,24.839-25.173l.167.167,37.059,37.059a17.644,17.644,0,0,1,.053,24.953l-.053.053"
          transform="translate(659.618 2410.556)"
        />
        <rect fill="none" width="460.571" height="373.649" transform="translate(700 2490)" />
      </g>
    </svg>
  );
}

export function DashboardNoRecord({
  labelKey = 'LBL_NO_RECORD_FOUND',
  labelFallback = 'No record found',
  headingKey,
  headingFallback,
  action,
}: Props) {
  const { lbl } = useSite();
  const heading = headingKey
    ? lbl(headingKey, headingFallback ?? labelFallback)
    : lbl(labelKey, labelFallback);

  return (
    <div className="message-display">
      <div className="message-display__icon">
        <NoRecordIcon />
      </div>
      <h5>{heading}</h5>
      {action}
    </div>
  );
}
