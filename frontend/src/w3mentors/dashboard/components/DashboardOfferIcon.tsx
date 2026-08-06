/** Legacy offer tag icon from `students/search.php`. */
export function DashboardOfferIcon({ className = 'icon icon--offer' }: { className?: string }) {
  return (
    <svg
      className={className}
      xmlns="http://www.w3.org/2000/svg"
      width={24}
      height={24}
      viewBox="0 0 24 24"
      aria-hidden
    >
      <path d="M10.117,2.1,2.5,3.189,1.414,10.8l7.071,7.071a.769.769,0,0,0,1.088,0l7.616-7.616a.769.769,0,0,0,0-1.088ZM9.573,3.732l5.984,5.983L9.029,16.243,3.046,10.26l.815-5.712,5.712-.815Zm-1.631,4.9a1.539,1.539,0,1,0-2.177,0A1.539,1.539,0,0,0,7.942,8.628Z" transform="translate(2.586 1.9)" />
    </svg>
  );
}
