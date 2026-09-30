import { Slot } from "@radix-ui/react-slot"
import { cva, type VariantProps } from "class-variance-authority"
import * as React from "react"

import { cn } from "@/lib/utils"

const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-control text-body font-semibold transition-[color,background-color,box-shadow] disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        outline:
          "border border-field bg-transparent text-ink hover:bg-accent",
        now:
          "bg-now text-on-now hover:bg-now/90",
        destructive:
          "bg-destructive text-white hover:bg-destructive/90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40",
        secondary:
          "bg-secondary text-secondary-foreground hover:bg-secondary/80",
        ghost: "hover:bg-accent hover:text-accent-foreground",
        link: "text-ink underline-offset-4 hover:underline",
        quiet:
          "font-normal text-muted-foreground underline-offset-4 hover:text-ink hover:underline",
      },
      size: {
        default: "h-11 px-4 has-[>svg]:px-3",
        sm: "h-11 px-3 has-[>svg]:px-2.5",
        icon: "size-11",
        action: "h-14 px-7 text-lead",
        control: "h-16 px-4",
      },
    },
    compoundVariants: [
      {
        variant: "quiet",
        className: "h-auto min-h-11 justify-start px-0 text-left whitespace-normal has-[>svg]:px-0",
      },
    ],
    defaultVariants: {
      variant: "outline",
      size: "default",
    },
  }
)

function Button({
  className,
  variant,
  size,
  asChild = false,
  ...props
}: React.ComponentProps<"button"> &
  VariantProps<typeof buttonVariants> & {
    asChild?: boolean
  }) {
  const Comp = asChild ? Slot : "button"

  return (
    <Comp
      data-slot="button"
      className={cn(buttonVariants({ variant, size, className }))}
      {...props}
    />
  )
}

export { Button, buttonVariants }
