export function Footer() {
  return (
    <footer className="bg-white border-t border-gray-200 px-6 py-3">
      <div className="flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-500">
        <span>Copyright FranchiseCare &copy; {new Date().getFullYear()}</span>
        <div className="text-right">
          <span>
            For <span className="font-semibold text-gray-700">Mate Support</span>, please call{' '}
            <span className="font-bold text-gray-800">03 9514 9606</span>
          </span>
          <br />
          <span>
            Monday – Friday: <span className="font-semibold text-gray-700">9:00 AM – 10:30 PM</span> | Saturday – Sunday:{' '}
            <span className="font-semibold text-gray-700">9:00 AM – 6:00 PM</span>
          </span>
        </div>
      </div>
    </footer>
  )
}
